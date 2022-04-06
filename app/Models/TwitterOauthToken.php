<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TwitterOauthToken extends Model
{
    //
    protected $fillable = ['token', 'secret'];

}
