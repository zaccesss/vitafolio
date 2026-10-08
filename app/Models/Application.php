<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** a role someone is applying for, saved from the Jobs page or added by hand */
class Application extends Model
{
    use HasFactory;

    /** in the order a search usually runs, so the tabs read left to right */
    public const STATUSES = ['saved', 'applied', 'assessment', 'interview', 'offer', 'rejected', 'withdrawn'];

    protected $fillable = ['user_id', 'job_listing_id', 'title', 'company', 'location', 'url', 'kind', 'status', 'applied_on', 'deadline', 'notes'];

    protected function casts(): array
    {
        return ['applied_on' => 'date', 'deadline' => 'date'];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'saved' => __('Saved'),
            'applied' => __('Applied'),
            'assessment' => __('Assessment'),
            'interview' => __('Interview'),
            'offer' => __('Offer'),
            'rejected' => __('Rejected'),
            'withdrawn' => __('Withdrawn'),
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<JobListing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }
}
