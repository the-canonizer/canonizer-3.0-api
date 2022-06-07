<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialEmailVerify extends Model
{
    
    protected $table = 'social_email_verify';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name', 'last_name', 'email', 'email_verified', 'provider', 'provider_id','code','created_at', 'updated_at', 'otp'];

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
            $model->created_at = $currentTimestamp;
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
