<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Util;

class Category extends Model {

    const VIDEO = 'video';
    const OTHER = 'other'; // Or you can define your own enum ...

    protected $table = 'categories';
    protected $fillable = ['title', 'type'];
    public $timestamps = true;

    protected $hidden = ['pivot', 'created_at', 'updated_at'];

    protected $guarded = [];
    
    public function getCreatedAtAttribute($date)
    {
        return Util::convertDateFormatToUnix($date);
    }

    public function getUpdatedAtAttribute($date)
    {
        return Util::convertDateFormatToUnix($date);
    }

    public function videos() {
        return $this->belongsToMany(Video::class, 'video_categories', 'category_id', 'video_id');
    }
}
