<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityUser extends Model
{
    protected $table = 'activity_users';
    protected $fillable = [
        'activity_id', 'user_id', 'viewed'
    ];
    public $timestamps = false;

    public static function boot()
    {
        parent::boot(); 
        self::creating(function($model){
            $currentTimestamp = time();
            $model->created_at = $currentTimestamp;
            $model->updated_at = $currentTimestamp;
        });
    }

    public function Activity()
    {
        return $this->belongsTo('\App\Models\ActivityLog', 'activity_id', 'id');
    }
    public function User()
    {
        return $this->belongsTo('\App\Models\User', 'user_id', 'id');
    }

}