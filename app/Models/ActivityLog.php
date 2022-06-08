<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Util;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    public function getCreatedAtAttribute($date)
    {
       return Util::convertDateFormatToUnix($date);
    }

    public function getUpdatedAtAttribute($date)
    {
        return Util::convertDateFormatToUnix($date);
    }
}
