<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SocialUser extends Model {

    protected $table = 'social_users';
    public $timestamps = false;

/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'social_email',
        'social_name',
        'provider',
        'provider_id',
    ];

    

    
}
