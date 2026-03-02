<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Video extends Model
{
    use HasFactory;
    protected $table = 'videos';
    protected $fillable = ['title', 'value', 'extension'];
    public $timestamps = true;

    protected $hidden = ['pivot', 'created_at', 'updated_at'];

    protected $guarded = [];

    public function resolutions() {
        return $this->belongsToMany(Resolution::class, 'video_resolutions', 'video_id', 'resolution_id');
    }

    public function categories() {
        return $this->belongsToMany(Category::class, 'video_categories', 'video_id', 'category_id');
    }
}
