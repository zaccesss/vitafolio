<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * a few words about a cv's owner from someone who worked or studied with them. it stays
 * pending until the owner approves it. only approved ones ever show to anyone else
 *
 * @property Carbon|null $approved_at
 * @property Carbon|null $hidden_at
 */
class Endorsement extends Model
{
    public const RELATIONSHIPS = [
        'worked_together' => 'Worked together',
        'managed' => 'Managed them',
        'managed_by' => 'Was managed by them',
        'studied_together' => 'Studied together',
        'taught' => 'Taught them',
        'mentored' => 'Mentored them',
        'client' => 'Worked with them as a client',
        'other' => 'Another connection',
    ];

    public const STATUSES = ['pending' => 'Waiting for approval', 'approved' => 'Shown on the CV', 'hidden' => 'Not shown'];

    public const MAX_LENGTH = 600;

    protected $fillable = ['relationship', 'context', 'body'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'hidden_at' => 'datetime'];
    }

    /** @return BelongsTo<Cv, $this> */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /** @return BelongsTo<User, $this> */
    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorser_id');
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** approved by the owner, not hidden by a moderator and written by an account in good standing */
    public function scopeShown(Builder $query): Builder
    {
        return $query->where('endorsements.status', 'approved')->whereNull('endorsements.hidden_at')
            ->whereHas('endorser', fn (Builder $q) => $q->whereNull('suspended_at'));
    }

    public function isShown(): bool
    {
        return $this->status === 'approved' && $this->hidden_at === null && ! $this->endorser->isSuspended();
    }

    public function relationshipLabel(): string
    {
        return self::RELATIONSHIPS[$this->relationship] ?? self::RELATIONSHIPS['other'];
    }

    /** the fields both parties receive in their data export */
    public function exportFields(): array
    {
        return [
            'relationship' => $this->relationshipLabel(),
            'context' => $this->context,
            'text' => $this->body,
            'status' => $this->hidden_at ? 'hidden by a moderator' : $this->status,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
