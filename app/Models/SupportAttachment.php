<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportAttachment extends Model
{
    protected $fillable = ['support_message_id', 'public_id', 'extension', 'original_name', 'size'];

    /** @return BelongsTo<SupportMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }
}
