<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Images;
use App\Support\Links;
use App\Support\ViewRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, string $handle): View|RedirectResponse
    {
        $user = User::where('handle', $handle)->first();
        if (! $user) {
            // a recently changed handle points people to the new one instead of a dead end
            $moved = DB::table('handle_history')->where('handle', $handle)->where('released_at', '>', now())->value('user_id');
            abort_unless($moved, 404);

            $target = User::findOrFail($moved);
            abort_unless($target->profileVisibleTo($request->user()), 404);

            return redirect()->route('profile.show', $target->handle, 301);
        }
        abort_unless($user->profileVisibleTo($request->user()), 404);

        $isOwner = $request->user()?->is($user) ?? false;
        ViewRecorder::recordProfile($user, $request, $request->user());
        $cvs = $user->cvs()->with('tags')
            ->when(! $isOwner, fn ($q) => $q->where('visibility', 'public')->whereNull('hidden_at'))
            ->get();

        return view('profile.show', [
            'user' => $user,
            'cvs' => $cvs,
            'links' => Links::parse($user->links),
            'isOwner' => $isOwner,
        ]);
    }

    public function edit(Request $request): View
    {
        return view('settings.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'pronouns' => ['nullable', 'string', 'max:30'],
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:600'],
            'location' => ['nullable', 'string', 'max:100'],
            'university' => ['nullable', 'string', 'max:100'],
            'availability' => ['required', Rule::in(array_keys(config('vitafolio.availability')))],
            'links' => ['nullable', 'string', 'max:1500'],
            'profile_visibility' => ['required', Rule::in(array_keys(config('vitafolio.profile_visibility')))],
        ]);
        $request->user()->update($data);

        return redirect()->route('profile.edit')->with('status', 'Your profile has been saved.');
    }

    public function updateHandle(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validateWithBag('handle', [
            'handle' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(User::RESERVED_HANDLES)],
        ], [
            'handle.regex' => 'Use lowercase letters, numbers and single hyphens only.',
            'handle.not_in' => 'That handle is reserved. Choose another.',
        ]);
        $new = $data['handle'];

        if ($new === $user->handle) {
            return redirect()->route('settings.handle');
        }
        if (! $user->canChangeHandle()) {
            throw ValidationException::withMessages(['handle' => 'You can change your handle again on '.$user->nextHandleChange()->format('j F Y').'.'])->errorBag('handle');
        }
        if (User::handleTaken($new, $user->id)) {
            throw ValidationException::withMessages(['handle' => 'That handle is taken. Try adding a word or number.'])->errorBag('handle');
        }

        DB::transaction(function () use ($user, $new) {
            // the old handle is held for a month so nobody else can take it and pose as this person
            DB::table('handle_history')->insert([
                'user_id' => $user->id,
                'handle' => $user->handle,
                'released_at' => now()->addDays(User::HANDLE_COOLDOWN_DAYS),
            ]);
            DB::table('handle_history')->where('user_id', $user->id)->where('handle', $new)->delete();
            $user->forceFill(['handle' => $new, 'handle_changed_at' => now()])->save();
        });

        return redirect()->route('settings.handle')->with('status', 'Your handle is now @'.$new.'. Links to your old handle redirect here for 30 days.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validateWithBag('avatar', [
            // 4000 pixels a side keeps the decoded image well inside the php memory limit
            'avatar' => ['required', 'file', 'max:'.config('vitafolio.limits.avatar_kb'), 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'dimensions:max_width=4000,max_height=4000'],
            // the cropper sends all three or none; without them the centre square is used
            'crop_x' => ['nullable', 'required_with:crop_y,crop_size', 'numeric', 'between:0,1'],
            'crop_y' => ['nullable', 'required_with:crop_x,crop_size', 'numeric', 'between:0,1'],
            'crop_size' => ['nullable', 'required_with:crop_x,crop_y', 'numeric', 'between:0.01,1'],
        ]);
        $crop = $request->filled('crop_size')
            ? [(float) $request->input('crop_x'), (float) $request->input('crop_y'), (float) $request->input('crop_size')]
            : null;
        $bytes = Images::squareJpeg($request->file('avatar')->getRealPath(), 600, $crop);
        if ($bytes === null) {
            throw ValidationException::withMessages(['avatar' => 'That image could not be read. Try a JPG or PNG.'])->errorBag('avatar');
        }
        $request->user()->forceFill([
            'avatar' => $bytes,
            'avatar_type' => 'image/jpeg',
            'avatar_version' => base_convert((string) now()->getTimestampMs(), 10, 36),
        ])->save();

        return redirect()->route('settings.photo')->with('status', 'Profile photo updated.');
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['avatar' => null, 'avatar_type' => null, 'avatar_version' => null])->save();

        return redirect()->route('settings.photo')->with('status', 'Profile photo removed.');
    }
}
