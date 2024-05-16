<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class EmbeddedCodeTracking extends Model
{
    protected $table = 'embedded_code_tracking';
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $currentTimestamp = time();
            $model->created_at = $currentTimestamp;
            $model->updated_at = $currentTimestamp;
        });
    }

    protected $fillable = ['url', 'ip_address', 'user_agent'];
}
