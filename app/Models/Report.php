<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public const REASONS = [
        'spam' => 'Spam or advertising',
        'impersonation' => 'Pretends to be someone else',
        'offensive' => 'Offensive or abusive content',
        'personal' => 'Shares someone else\'s personal information',
        'other' => 'Something else',
    ];

    protected $fillable = ['reason', 'details', 'reporter_hash', 'endorsement_id'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    /** @return BelongsTo<Cv, $this> */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /**
     * set when the report is about one endorsement on the cv rather than the cv itself
     *
     * @return BelongsTo<Endorsement, $this>
     */
    public function endorsement(): BelongsTo
    {
        return $this->belongsTo(Endorsement::class);
    }
}
