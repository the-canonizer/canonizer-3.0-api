<?php

namespace App\Models;

use Carbon\Carbon;
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;


class Thread extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, HasApiTokens  , Authorizable, HasFactory;
    
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

    public function getCreatedAtAttribute($date)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $date)->timestamp;
    }

    public function getUpdatedAtAttribute($date)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $date)->timestamp;
    }

    public function getPostUpdatedAtAttribute($date)
    {
        return Carbon::parse($date)->timestamp;
    }
}
