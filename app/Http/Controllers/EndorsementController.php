<?php

namespace App\Http\Controllers;

use App\Mail\EndorsementReceived;
use App\Models\Cv;
use App\Models\Endorsement;
use App\Rules\Turnstile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EndorsementController extends Controller
{
    /** written by a signed-in, verified account about someone else's cv that others can already see */
    public function store(Request $request, Cv $cv): RedirectResponse
    {
        $user = $request->user();
        abort_unless($cv->isVisibleTo($user), 404);
        // only on a cv anyone else could open, so an admin cannot endorse a private or hidden one
        abort_unless($cv->isVisibleTo(null), 404);
        abort_if($user->id === $cv->user_id, 403, 'You cannot endorse your own CV.');
        abort_if($user->isSuspended(), 403);

        if ($cv->endorsements()->where('endorser_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['body' => 'You have already endorsed this CV. You can edit your endorsement instead.'])
                ->errorBag('endorsement')->redirectTo(route('cv.show', $cv).'#endorse');
        }

        $data = $this->validated($request, $cv);
        try {
            $endorsement = new Endorsement($data);
            $endorsement->cv()->associate($cv);
            $endorsement->endorser()->associate($user);
            $endorsement->save();
        } catch (UniqueConstraintViolationException) {
            // two submissions at once: the first one wins and the second is told it already exists
            return redirect()->to(route('cv.show', $cv).'#endorse')->with('status', 'Your endorsement has already been sent.');
        }

        // sent after the response, so a slow mail service never holds up the endorser
        $owner = $cv->user;
        defer(fn () => rescue(fn () => Mail::to($owner->email)->send(new EndorsementReceived($endorsement)), report: false));

        return redirect()->to(route('cv.show', $cv).'#endorse')
            ->with('status', 'Thanks. Your endorsement shows on this CV once '.$owner->firstName().' approves it.');
    }

    /** an edited endorsement needs approving again, so the owner never shows words they have not read */
    public function update(Request $request, Cv $cv, Endorsement $endorsement): RedirectResponse
    {
        abort_unless($endorsement->endorser_id === $request->user()->id, 403);
        abort_unless($cv->isVisibleTo($request->user()) && $cv->isVisibleTo(null), 404);
        abort_if($request->user()->isSuspended(), 403);
        $endorsement->fill($this->validated($request, $cv));
        if ($endorsement->isDirty() && $endorsement->status === 'approved') {
            $endorsement->status = 'pending';
            $endorsement->approved_at = null;
        }
        $endorsement->save();

        return redirect()->to(route('cv.show', $cv).'#endorse')->with('status', $endorsement->status === 'pending'
            ? 'Your endorsement has been updated. It shows again once '.$cv->user->firstName().' approves the new wording.'
            : 'Your endorsement has been updated.');
    }

    /** the endorser withdraws it or the cv's owner deletes it */
    public function destroy(Request $request, Cv $cv, Endorsement $endorsement): RedirectResponse
    {
        $user = $request->user();
        $isOwner = $user->id === $cv->user_id;
        abort_unless($isOwner || $endorsement->endorser_id === $user->id, 403);
        // withdrawing would wipe a moderator's decision and its reports, then allow the same text again
        abort_if(! $isOwner && $endorsement->hidden_at !== null, 403, 'A moderator has hidden this endorsement, so it cannot be withdrawn.');
        $endorsement->delete();

        return $isOwner
            ? redirect()->route('cvs.edit', [$cv, 'endorsements'])->with('status', 'The endorsement has been deleted.')
            : redirect()->to(route('cv.show', $cv).'#endorse')->with('status', 'Your endorsement has been withdrawn.');
    }

    /** the owner approves an endorsement so it shows or hides it again */
    public function decide(Request $request, Cv $cv, Endorsement $endorsement): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'hidden'])]]);
        $endorsement->status = $data['status'];
        $endorsement->approved_at = $data['status'] === 'approved' ? now() : null;
        $endorsement->save();

        return redirect()->route('cvs.edit', [$cv, 'endorsements'])->with('status', $data['status'] === 'approved'
            ? 'The endorsement from '.$endorsement->endorser->name.' now shows on this CV.'
            : 'The endorsement from '.$endorsement->endorser->name.' is hidden. Only you can see it.');
    }

    private function validated(Request $request, Cv $cv): array
    {
        $rules = [
            'relationship' => ['required', Rule::in(array_keys(Endorsement::RELATIONSHIPS))],
            'context' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:20', 'max:'.Endorsement::MAX_LENGTH],
            // the rule alone is skipped when the field is missing, so it is also required while switched on
            'cf-turnstile-response' => Turnstile::enabled() ? ['required', new Turnstile] : [],
        ];
        $validator = validator($request->all(), $rules, [], ['body' => 'endorsement']);
        if ($validator->fails()) {
            throw (new ValidationException($validator))->errorBag('endorsement')->redirectTo(route('cv.show', $cv).'#endorse');
        }

        return collect($validator->validated())->only(['relationship', 'context', 'body'])->all();
    }
}
