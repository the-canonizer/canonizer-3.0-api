<?php

namespace App\Models;

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
            $model->created_at = date('Y-m-d H:i:s');
            $model->updated_at = date('Y-m-d H:i:s');
        });

        self::created(function($model){
            // ... code here
        });

        self::updating(function($model){
            $model->updated_at = date('Y-m-d H:i:s');
        });

        self::updated(function($model){
            // ... code here
        });

        self::deleting(function($model){
            $model->updated_at = date('Y-m-d H:i:s');
        });

        self::deleted(function($model){
            // ... code here
        });
    }

    // Fillable Columns

    protected $fillable = ['thread_id', 'user_id', 'body', 'is_delete'];

    public function owner()
    {
        return $this->belongsTo('App\Model\Nickname'::class, 'user_id');
    }
}
