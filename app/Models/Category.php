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

    /**
     * Videos table (Add thumbnails in video table as well)
     * Resoulution table
     * video_resolutions table
     * category_table
     * category table seeder
     * video_category table 
     * video_category seeder
     * Thumbnail seeder
     */


    public function getCreatedAtAttribute($date)
    {
        return Util::convertDateFormatToUnix($date);
    }

    public function getUpdatedAtAttribute($date)
    {
        return Util::convertDateFormatToUnix($date);
    }

    public function videos() {
        return $this->belongsToMany(Video::class, 'video_categories', 'category_id', 'video_id')->as('videos');
    }
}
