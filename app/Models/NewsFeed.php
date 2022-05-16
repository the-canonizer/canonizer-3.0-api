<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsFeed extends Model
{
    protected $table = 'news_feed';
    public $timestamps = false;

    public function nickName()
    {
        return $this->belongsTo('\App\Models\Nickname', 'submitter_nick_id', 'id');
    }
}
