<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model {
    use HasFactory;

    protected $dateFormat = 'U';
    
    protected $table = 'tags';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title','is_active','parent_id', 'created_at', 'updated_at', 'deleted_at'];

    public function topics() {
        return $this->belongsToMany(Topic::class, 'topics_tags', 'tag_id', 'topic_num', 'topic_num');
    }
}
