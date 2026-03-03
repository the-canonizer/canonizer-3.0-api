<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

#[AllowDynamicProperties]
class Tree extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'trees';
    protected $guarded = [];

    protected $dates = ['created_at'];
}
