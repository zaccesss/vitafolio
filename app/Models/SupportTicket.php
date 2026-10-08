<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    /** what a ticket can be about, in the order the form lists them */
    public const CATEGORIES = ['account', 'cv', 'check', 'jobs', 'privacy', 'bug', 'other'];

    /** open: waiting for staff. waiting: staff replied and await the person. resolved and closed: finished */
    public const STATUSES = ['open', 'waiting', 'resolved', 'closed'];

    protected $fillable = ['user_id', 'name', 'email', 'category', 'subject', 'status', 'token_hash', 'last_activity_at'];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }

    /** @return array<string, string> */
    public static function categoryLabels(): array
    {
        return [
            'account' => __('Account and sign-in'),
            'cv' => __('Building or sharing a CV'),
            'check' => __('Check a CV'),
            'jobs' => __('Jobs'),
            'privacy' => __('Privacy and your data'),
            'bug' => __('Something is broken'),
            'other' => __('Something else'),
        ];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'open' => __('Open'),
            'waiting' => __('Waiting on you'),
            'resolved' => __('Resolved'),
            'closed' => __('Closed'),
        ];
    }

    /** the number people quote, such as VF-1042 */
    public function reference(): string
    {
        return 'VF-'.(1000 + $this->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->oldest();
    }

    /** the person who opened it, an admin or a visitor holding the private link may see it */
    public function canBeSeenBy(?User $user, ?string $token): bool
    {
        if ($user && ($user->isAdmin() || $user->id === $this->user_id)) {
            return true;
        }

        return filled($token) && hash_equals($this->token_hash, hash('sha256', (string) $token));
    }
}
