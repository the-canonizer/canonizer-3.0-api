<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;


class Topic extends Model {

    protected $table = 'topic';
    public $timestamps = false;

    public static function TopicLink($topicNum, $campNum = 1 , $title, $campName = 'Aggreement'){
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" .$campName;
        $queryString = (app('request')->getQueryString()) ? '?'.app('request')->getQueryString() : "";
        return $link = url('topic/' . $topicId . '/' . $campId);
    }

    
}
