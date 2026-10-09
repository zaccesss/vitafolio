<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * a visitor's message whose email could not be sent. it is retried by the nightly tidy-up and deleted
 * once delivered or after KEEP_DAYS, so nothing personal is stored longer than needed
 *
 * @property array{sender_name: string, sender_email: string, message: string} $payload
 */
class PendingMessage extends Model
{
    public const KEEP_DAYS = 14;

    protected $fillable = ['cv_id', 'payload', 'attempts'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array'];
    }

    /** @return BelongsTo<Cv, $this> */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }
}
