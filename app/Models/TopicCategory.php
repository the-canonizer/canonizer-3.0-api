<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopicCategory extends Model
{
    protected $table = 'topic_categories';

    protected $fillable = ['name', 'description'];

    public function topics()
    {
        return $this->hasMany(Topic::class, 'category_id');
    }
}
