<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsensusVideoPodcast extends Model
{
    use HasFactory;
    protected $table = 'consensus_video_podcast';
    public $timestamps = false;
    // Fillable Columns

    protected $fillable = ['id', 'title','description','video_link', 'active', 'created_at', 'updated_at', 'deleted_at'];

    
}
