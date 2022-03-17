<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;


class Camp extends Model {

    protected $table = 'camp';
    public $timestamps = false;


    public static function campLink($topicNum,$campNum,$title,$campName){
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" .$campName;
        $queryString = (app('request')->getQueryString()) ? '?'.app('request')->getQueryString() : "";
        return $link = url('topic/' . $topicId . '/' . $campId .'#statement');
    }



    
}
