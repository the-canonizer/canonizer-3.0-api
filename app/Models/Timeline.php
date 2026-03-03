<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

#[AllowDynamicProperties]
class Timeline extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'timelines';
    protected $guarded = [];
    protected $dates = ['created_at'];
}
