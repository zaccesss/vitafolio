<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Rules\Turnstile;
use App\Support\MessageRelay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /** messages to the site owner from the about page */
    public function site(Request $request): RedirectResponse
    {
        $data = $this->validated($request, 'default');
        // a filled hidden field means a bot, so it is told the message went through
        if ($data !== null && filled(config('vitafolio.contact_email'))) {
            MessageRelay::send($data);
        }

        return redirect()->route('contact.sent');
    }

    /** relays a message to a cv owner without ever revealing their address */
    public function cv(Request $request, Cv $cv): RedirectResponse
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $data = $this->validated($request, 'message');
        if ($data !== null) {
            MessageRelay::send($data, $cv);
        }

        return redirect()->to(route('cv.show', $cv).'#message')->with('status', __('Your message has been sent. They can reply to you directly.'));
    }

    private function validated(Request $request, string $bag): ?array
    {
        $data = $request->validateWithBag($bag, [
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_email' => ['required', 'email', 'max:254'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'cf-turnstile-response' => [new Turnstile],
        ]);

        return filled($request->input('website')) ? null : $data;
    }
}
