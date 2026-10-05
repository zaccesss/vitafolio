<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug'];

    /** @return BelongsToMany<Cv, $this> */
    public function cvs(): BelongsToMany
    {
        return $this->belongsToMany(Cv::class);
    }
}
