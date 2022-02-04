<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Namespaces extends Model
{
    protected $table = 'namespace';
    public $timestamps = false;

    protected $guarded = [];
}
