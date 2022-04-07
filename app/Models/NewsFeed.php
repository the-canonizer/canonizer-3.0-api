<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsFeed extends Model
{
    protected $table = 'news_feed';
    public $timestamps = false;

    public static function responseIndexes(){
        return [
            'id', 'display_text', 'link', 'available_for_child'
        ];
    }
}
