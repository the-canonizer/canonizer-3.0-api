<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoPodcast extends Model
{
    protected $table = 'videopodcast';
    public $timestamps = true;

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = [];
}
