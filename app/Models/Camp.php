<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;

class Camp extends Model
{
    protected $table = 'camp';
    public $timestamps = false;
    const AGREEMENT_CAMP = "Agreement";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_num', 'parent_camp_num', 'key_words', 'language', 'note', 'submit_time', 'submitter_nick_id', 'go_live_time', 'title', 'camp_name', 'camp_num'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public static function getLiveCamp($topicNum, $campNum, $filter)
    {
        switch ($filter) {
            case "default":
                return self::where('topic_num', $topicNum)
                    ->where('camp_num', '=', $campNum)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', time())
                    ->latest('submit_time')->first();
                break;
            case "review":
                return self::where('topic_num', $topicNum)
                    ->where('camp_num', '=', $campNum)
                    ->where('objector_nick_id', '=', NULL)
                    ->latest('submit_time')->first();
                break;
            case "bydate":
                $asOfDate = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
                return self::where('topic_num', $topicNum)
                    ->where('camp_num', '=', $campNum)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', $asOfDate)
                    ->latest('submit_time')->first();
                break;
            default:
                return self::where('topic_num', $topicNum)
                    ->where('objector_nick_id', '=', NULL)
                    ->latest('submit_time')->first();
        }
    }

    public static function campLink($topicNum, $campNum, $title, $campName)
    {
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" . $campName;
        $queryString = (app('request')->getQueryString()) ? '?' . app('request')->getQueryString() : "";
        return $link = url('topic/' . $topicId . '/' . $campId . '#statement');
    }

    public static function getAllParentCamp($topicNum, $filter, $asOfDate = null)
    {
        if($filter == 'bydate'){
            $asOfDate = strtotime(date('Y-m-d H:i:s', strtotime($asOfDate)));
        }else{
            $asOfDate = time();
        }

        return self::where('topic_num', $topicNum)
        ->where('objector_nick_id', '=', NULL)
        ->where('go_live_time', '<=', $asOfDate)
        ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNum . ' and objector_nick_id is null and go_live_time < '.$asOfDate.' group by camp_num)')
        ->orderBy('submit_time', 'desc')->orderBy('camp_name','desc')->groupBy('camp_num')->get();
    }
}
