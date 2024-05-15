<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Util;

class VideoCategory extends Model
{
    protected $table = 'video_categories';
    public $timestamps = true;
    protected $guarded = [];

    public function getCreatedAtAttribute($date)
    {
        return Util::convertDateFormatToUnix($date);
    }

    public function getUpdatedAtAttribute($date)
    {
        return Util::convertDateFormatToUnix($date);
    }
}
