<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaTag extends Model
{
    protected $table = 'meta_tags';
    public $timestamps = true;

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = [];

    protected $casts = [
        'keywords' => 'array',
    ];
}
