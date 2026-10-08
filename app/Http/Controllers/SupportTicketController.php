<?php

namespace App\Http\Controllers;

use App\Mail\TicketUpdate;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Rules\Turnstile;
use App\Support\Cloudinary;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Support tickets: anyone can open one, signed in or not. A visitor reaches theirs through a private
 * link sent by email, since there is no account to sign in to. Messages are Markdown and may carry
 * up to three screenshots, kept as private files only the people on the ticket can fetch.
 */
class SupportTicketController extends Controller
{
    public const MAX_BODY = 5000;

    public const MAX_IMAGES = 3;

    public function create(Request $request): View
    {
        return view('support.new', ['user' => $request->user()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:100'],
            'email' => [$user ? 'nullable' : 'required', 'email', 'max:254'],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'subject' => ['required', 'string', 'min:4', 'max:150'],
            'body' => ['required', 'string', 'min:10', 'max:'.self::MAX_BODY],
            'images' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'images.*' => ['image', 'mimes:png,jpg,jpeg,webp,gif', 'max:5120'],
            'cf-turnstile-response' => $user ? [] : [new Turnstile],
        ]);
        // a filled hidden field means a bot, so it is told the ticket went through
        if (filled($request->input('website'))) {
            return redirect()->route('support.sent');
        }

        $token = Str::random(40);
        $ticket = DB::transaction(function () use ($data, $user, $token, $request) {
            $ticket = SupportTicket::create([
                'user_id' => $user?->id,
                'name' => $user?->name ?? $data['name'],
                'email' => $user?->email ?? $data['email'],
                'category' => $data['category'],
                'subject' => $data['subject'],
                'status' => 'open',
                'token_hash' => hash('sha256', $token),
                'last_activity_at' => now(),
            ]);
            $this->addMessage($ticket, $user, false, $data['body'], $request->file('images', []));

            return $ticket;
        });

        $link = route('support.show', ['ticket' => $ticket, 'token' => $token]);
        defer(function () use ($ticket, $link) {
            rescue(fn () => Mail::to($ticket->email)->send(new TicketUpdate($ticket, 'opened', $link)));
            $this->tellAdmins($ticket, 'new');
        });

        return $user
            ? redirect()->route('support.show', $ticket)->with('status', __('Your ticket :ref is open. We will reply by email.', ['ref' => $ticket->reference()]))
            : redirect()->route('support.sent')->with('reference', $ticket->reference());
    }

    public function index(Request $request): View
    {
        return view('support.index', [
            'tickets' => $request->user()->hasMany(SupportTicket::class)->latest('last_activity_at')->paginate(20),
        ]);
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $token = (string) $request->query('token');
        abort_unless($ticket->canBeSeenBy($request->user(), $token), 404);

        return view('support.show', [
            'ticket' => $ticket->load('messages.attachments'),
            'token' => $request->user() ? null : $token,
            'staff' => (bool) $request->user()?->isAdmin(),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $token = (string) $request->input('token');
        $user = $request->user();
        abort_unless($ticket->canBeSeenBy($user, $token), 404);
        abort_if($ticket->status === 'closed', 403);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:'.self::MAX_BODY],
            'images' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'images.*' => ['image', 'mimes:png,jpg,jpeg,webp,gif', 'max:5120'],
            'resolve' => ['nullable', 'boolean'],
        ]);

        // an admin replying on someone else's ticket answers as staff; on their own ticket they are the person
        $staff = $user?->isAdmin() && $user->id !== $ticket->user_id;
        $this->addMessage($ticket, $user, $staff, $data['body'], $request->file('images', []));
        $ticket->update([
            'status' => $staff ? ($request->boolean('resolve') ? 'resolved' : 'waiting') : 'open',
            'last_activity_at' => now(),
        ]);

        if ($staff) {
            // the person gets a fresh private link only when they have no account to sign in with
            $link = $ticket->user_id ? route('support.show', $ticket) : null;
            defer(fn () => rescue(fn () => Mail::to($ticket->email)->send(new TicketUpdate($ticket, 'reply', $link))));
        } else {
            defer(fn () => $this->tellAdmins($ticket, 'person-reply'));
        }

        return redirect()->to(route('support.show', array_filter(['ticket' => $ticket, 'token' => $user ? null : $token])).'#latest')
            ->with('status', __('Your reply has been added.'));
    }

    public function attachment(Request $request, SupportAttachment $attachment): Response
    {
        $ticket = $attachment->message->ticket;
        abort_unless($ticket->canBeSeenBy($request->user(), (string) $request->query('token')), 404);
        $bytes = Cloudinary::fetchFile($attachment->public_id);
        abort_if($bytes === null, 404);
        $type = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'][$attachment->extension] ?? 'application/octet-stream';

        return response($bytes, 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @param  array<int, UploadedFile>  $images */
    private function addMessage(SupportTicket $ticket, ?User $user, bool $staff, string $body, array $images): void
    {
        $message = SupportMessage::create(['support_ticket_id' => $ticket->id, 'user_id' => $user?->id, 'from_staff' => $staff, 'body' => $body]);
        if (! Cloudinary::enabled()) {
            return;
        }
        foreach (array_slice($images, 0, self::MAX_IMAGES) as $image) {
            $extension = strtolower($image->extension() ?: 'png');
            $publicId = Cloudinary::storeFile((string) file_get_contents($image->getRealPath()), 'vitafolio/support', $extension);
            if ($publicId) {
                SupportAttachment::create([
                    'support_message_id' => $message->id,
                    'public_id' => $publicId,
                    'extension' => $extension,
                    'original_name' => Str::limit($image->getClientOriginalName(), 140, ''),
                    'size' => (int) $image->getSize(),
                ]);
            }
        }
    }

    private function tellAdmins(SupportTicket $ticket, string $event): void
    {
        User::where('role', 'admin')->pluck('email')->each(fn (string $email) => rescue(
            fn () => Mail::to($email)->locale(Locales::DEFAULT)->send(new TicketUpdate($ticket, $event, route('support.show', $ticket)))
        ));
    }
}
