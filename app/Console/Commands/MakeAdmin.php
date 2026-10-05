<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('vitafolio:make-admin {email : Email address of an existing account} {--revoke : Remove admin rights instead}')]
#[Description('Give an existing account the admin role or take it away')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $user = User::where('email', strtolower((string) $this->argument('email')))->first();
        if (! $user) {
            $this->error('No account uses that email address.');

            return self::FAILURE;
        }
        $user->forceFill(['role' => $this->option('revoke') ? 'member' : 'admin'])->save();
        $this->info($user->email.' is now '.($user->isAdmin() ? 'an admin.' : 'a member.'));

        return self::SUCCESS;
    }
}
