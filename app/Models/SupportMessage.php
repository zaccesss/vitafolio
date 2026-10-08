<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportMessage extends Model
{
    protected $fillable = ['support_ticket_id', 'user_id', 'from_staff', 'body'];

    protected function casts(): array
    {
        return ['from_staff' => 'boolean'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportAttachment::class);
    }

    /** the message as Markdown, with raw HTML stripped and only safe links, so it can never run script */
    public function html(): string
    {
        return Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 10]);
    }
}
