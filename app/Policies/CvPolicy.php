<?php

namespace App\Policies;

use App\Models\Cv;
use App\Models\User;

class CvPolicy
{
    public function manage(User $user, Cv $cv): bool
    {
        return $user->id === $cv->user_id;
    }

    public function create(User $user): bool
    {
        return $user->cvs()->count() < (int) config('vitafolio.max_cvs_per_user');
    }
}
