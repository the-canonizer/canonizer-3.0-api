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


class Reply extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, HasApiTokens  , Authorizable, HasFactory;
  
    protected $table = 'post';
    public $timestamps = false;


    public static function boot()
    {
        parent::boot(); 

        self::creating(function($model){
            $currentTimestamp = time();
            $model->created_at = $currentTimestamp;
            $model->updated_at = $currentTimestamp;
        });

        self::created(function($model){
            // ... code here
            $model->thread_id = $model->c_thread_id;
        });

        self::updating(function($model){
            $model->updated_at = time();
        });

        self::updated(function($model){
            // ... code here
            $model->thread_id = $model->c_thread_id;
        });

        self::deleting(function($model){
            $model->updated_at = time();
        });

        self::deleted(function($model){
            // ... code here
        });

        self::retrieved(function($model){
            $model->thread_id = $model->c_thread_id;
        });
    }

    // Fillable Columns

    protected $fillable = ['c_thread_id', 'user_id', 'body', 'is_delete'];

    public function owner()
    {
        return $this->belongsTo('App\Model\Nickname'::class, 'user_id');
    }

}
