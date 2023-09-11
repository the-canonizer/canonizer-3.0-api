<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotTopic extends Model
{
    use HasFactory;

    protected $dateFormat = 'U';
    
    protected $table = 'hot_topics';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'description', 'topic_num', 'camp_num', 'file_relative_path', 'file_full_path','active','created_at', 'updated_at', 'deleted_at'];
}
