<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvDocument extends Model
{
    protected $fillable = ['cv_id', 'filename', 'mime', 'size', 'source', 'storage', 'public_id', 'data'];

    protected $hidden = ['data'];

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf';
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576 ? round($this->size / 1048576, 1).' MB' : max(1, round($this->size / 1024)).' KB';
    }
}
