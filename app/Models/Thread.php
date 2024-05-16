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
            $currentTimestamp = time();
            $model->created_at = $currentTimestamp;
            $model->updated_at = $currentTimestamp;
        });
        self::created(function ($model) {
            // ... code here
        });
        self::updating(function ($model) {
            $model->updated_at = time();
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

    public function replies()
    {
        return $this->hasMany(Reply::class, 'c_thread_id', 'id');
    }

    public function latestReply()
    {
        return $this->hasOne(Reply::class, 'c_thread_id', 'id')->latestOfMany('updated_at');
    }

    public function topic()
    {
        return $this->hasOne(Topic::class, 'topic_num', 'topic_id')
            ->where('go_live_time', '<=', time())
            ->where('objector_nick_id', '=', NULL)
            ->orderBy('go_live_time', 'DESC');
    }
}
