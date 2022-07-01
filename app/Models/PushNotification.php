<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  PushNotification extends Model
{
    
    protected $table = 'push_notification';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'topic_num', 'camp_num', 'notification_type', 'message_body', 'fcm_token','is_read','created_at', 'updated_at'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $currentTimestamp = time();
            $model->created_at = $currentTimestamp;
            $model->updated_at = $currentTimestamp;
        });
        self::created(function ($model) {
            // ... code here
        });
        self::updating(function ($model) {
            $currentTimestamp = time();
            $model->updated_at = $currentTimestamp;
        });
        self::updated(function ($model) {
            // ... code here
        });
        self::deleting(function ($model) {
            $model->updated_at = time();
        });
        self::deleted(function ($model) {
            // ... code here
        });
    }
}
