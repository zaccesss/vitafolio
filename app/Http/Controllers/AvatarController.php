<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AvatarController extends Controller
{
    public function __invoke(Request $request, User $user): Response
    {
        // the version only appears on pages the viewer was allowed to see, so a guessed handle is not enough
        abort_unless($user->hasAvatar() && $request->query('v') === $user->avatar_version && $this->visible($request, $user), 404);
        $bytes = DB::table('users')->where('id', $user->id)->value('avatar');

        return response($bytes, 200, [
            'Content-Type' => $user->avatar_type,
            'X-Content-Type-Options' => 'nosniff',
            // the url carries the upload version, so a long cache only lasts until the next change
            'Cache-Control' => $user->profile_visibility === 'public' ? 'public, max-age=31536000, immutable' : 'private, max-age=3600',
        ]);
    }

    /**
     * a photo shows wherever its owner shows up: a visible profile or a cv someone can open.
     * a suspended account shows nowhere, except to its owner and admins
     */
    private function visible(Request $request, User $user): bool
    {
        if ($user->profileVisibleTo($request->user())) {
            return true;
        }

        return ! $user->isSuspended()
            && $user->cvs()->where('visibility', '<>', 'private')->whereNull('hidden_at')->exists();
    }
}
