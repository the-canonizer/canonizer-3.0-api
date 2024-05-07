<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialMediaLink extends Model
{
    protected $table = 'social_links';
    public $timestamps = true;

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = [];
}
