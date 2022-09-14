<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermAndServices extends Model
{
    protected $table = 'term_and_services';
    public $timestamps = true;

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = [];
}