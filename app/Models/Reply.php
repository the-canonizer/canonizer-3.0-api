<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reply extends Model
{
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
