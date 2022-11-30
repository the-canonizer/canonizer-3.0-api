<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoResolution extends Model
{
    protected $table = 'video_resolutions';
    protected $fillable = ['title', 'resolution'];
    public $timestamps = true;

    protected $guarded = [];
    
}
