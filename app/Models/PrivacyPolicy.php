<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyPolicy extends Model
{
    protected $table = 'privacy_policies';
    public $timestamps = true;

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = [];
}
