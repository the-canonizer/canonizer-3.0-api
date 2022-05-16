<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityUser extends Model
{
    protected $table = 'activity_users';
    protected $fillable = [
        'activity_id', 'user_id', 'viewed'
    ];

    public function Activity(){
        return $this->belongsTo('\App\Models\ActivityLog','activity_id','id');
    }
    public function User(){
        return $this->belongsTo('\App\Models\User','user_id','id');
    }
}
