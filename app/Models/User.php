<?php

namespace App\Models;

use App\Support\IndexNow;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property Carbon|null $handle_changed_at
 * @property Carbon|null $suspended_at
 */
#[Fillable(['name', 'email', 'password', 'handle', 'pronouns', 'headline', 'bio', 'location', 'university', 'availability', 'links', 'profile_visibility'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'avatar'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    // handles can change once a month; an old one stays reserved for a month after
    public const HANDLE_COOLDOWN_DAYS = 30;

    public const RESERVED_HANDLES = [
        'admin', 'administrator', 'api', 'about', 'account', 'app', 'help', 'login', 'logout', 'mod',
        'moderator', 'privacy', 'register', 'root', 'security', 'settings', 'signin', 'signup', 'staff',
        'support', 'system', 'terms', 'vitafolio', 'www',
    ];

    public function getRouteKeyName(): string
    {
        return 'handle';
    }

    /** search engines hear about public profile changes, including a profile that just went private */
    protected static function booted(): void
    {
        // each cv is deleted on its own first, so its files and media leave cloudinary too
        static::deleting(fn (User $user) => $user->cvs()->get()->each->delete());

        static::saved(function (User $user) {
            $fields = ['name', 'headline', 'bio', 'location', 'university', 'availability', 'links', 'avatar_version', 'handle', 'profile_visibility', 'suspended_at'];
            $wasPublic = $user->getOriginal('profile_visibility') === 'public' && $user->getOriginal('suspended_at') === null;
            $isPublic = $user->profile_visibility === 'public' && ! $user->isSuspended() && $user->hasVerifiedEmail();
            if (! $user->wasChanged($fields) || ! ($wasPublic || $isPublic)) {
                return;
            }
            $urls = [route('profile.show', $user->handle)];
            if ($user->wasChanged('handle') && $user->getOriginal('handle')) {
                $urls[] = route('profile.show', $user->getOriginal('handle'));
            }
            IndexNow::submit(...$urls);
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /** bytes of cv files and project media this account stores, counted against its allowance */
    public function storageUsed(): int
    {
        $cvIds = $this->cvs()->pluck('id');

        return (int) CvDocument::whereIn('cv_id', $cvIds)->sum('size')
            + (int) Project::whereIn('cv_id', $cvIds)->sum('media_size');
    }

    public function storageAllowance(): int
    {
        return (int) config('vitafolio.limits.storage_mb') * 1024 * 1024;
    }

    /** whether $extra more bytes fit, after giving back $replacing bytes for a file being swapped out */
    public function canStore(int $extra, int $replacing = 0): bool
    {
        return $this->storageUsed() - $replacing + $extra <= $this->storageAllowance();
    }

    /**
     * google, github, microsoft and linkedin accounts connected for signing in
     *
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * an account can keep several named cvs, each with its own privacy setting
     *
     * @return HasMany<Cv, $this>
     */
    public function cvs(): HasMany
    {
        return $this->hasMany(Cv::class)->latest('updated_at');
    }

    /**
     * get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'has_password' => 'boolean',
            'suspended_at' => 'datetime',
            'handle_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasAvatar(): bool
    {
        return $this->avatar_version !== null;
    }

    public function avatarUrl(): ?string
    {
        return $this->hasAvatar() ? route('avatar', ['user' => $this->handle, 'v' => $this->avatar_version]) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return Str::upper(implode('', array_map(fn ($p) => Str::substr($p, 0, 1), array_slice($parts, 0, 2)))) ?: '?';
    }

    public function firstName(): string
    {
        return Str::before(trim($this->name), ' ');
    }

    /** the owner and admins always see it; anyone else needs a profile that is not private or suspended */
    public function profileVisibleTo(?User $viewer): bool
    {
        if ($viewer && ($viewer->is($this) || $viewer->isAdmin())) {
            return true;
        }

        return $this->profile_visibility !== 'private' && ! $this->isSuspended();
    }

    public function canChangeHandle(): bool
    {
        return $this->handle_changed_at === null || $this->handle_changed_at->lte(now()->subDays(self::HANDLE_COOLDOWN_DAYS));
    }

    public function nextHandleChange(): ?Carbon
    {
        return $this->canChangeHandle() ? null : $this->handle_changed_at->copy()->addDays(self::HANDLE_COOLDOWN_DAYS);
    }

    /** builds an unused, valid handle from a name, for example jordan-smith or jordan-smith-2 */
    public static function suggestHandle(string $name, ?int $exceptId = null): string
    {
        $base = Str::limit(Str::slug($name), 26, '') ?: 'member';
        if (strlen($base) < 3 || in_array($base, self::RESERVED_HANDLES, true)) {
            $base = 'member-'.$base;
        }
        $handle = $base;
        for ($n = 2; static::handleTaken($handle, $exceptId); $n++) {
            $handle = $base.'-'.$n;
        }

        return $handle;
    }

    /** taken by an account or still held for someone who recently moved away from it */
    public static function handleTaken(string $handle, ?int $exceptId = null): bool
    {
        return static::where('handle', $handle)->when($exceptId, fn ($q) => $q->where('id', '<>', $exceptId))->exists()
            || DB::table('handle_history')->where('handle', $handle)
                ->where('released_at', '>', now())->when($exceptId, fn ($q) => $q->where('user_id', '<>', $exceptId))->exists();
    }
}
