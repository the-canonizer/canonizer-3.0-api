<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $table = 'thread';
    public $timestamps = false;

    protected $fillable = ['user_id', 'title', 'body', 'camp_id', 'topic_id'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->created_at = date('Y-m-d H:i:s');
            $model->updated_at = date('Y-m-d H:i:s');
        });
        self::created(function ($model) {
            // ... code here
        });
        self::updating(function ($model) {
            $model->updated_at = date('Y-m-d H:i:s');
        });
        self::updated(function ($model) {
            // ... code here
        });
        self::deleting(function ($model) {
            $model->updated_at = date('Y-m-d H:i:s');
        });
        self::deleted(function ($model) {
            // ... code here
        });
    }
}
