<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvView extends Model
{
    public $timestamps = false;

    protected $fillable = ['cv_id', 'viewed_on', 'visitor_hash', 'referrer_host'];
}
