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

    public static function getLiveCamp($topicnum, $campnum, $filter)
    {

        switch ($filter) {
            case "default":
                return self::where('topic_num', $topicnum)
                    ->where('camp_num', '=', $campnum)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', time())
                    ->latest('submit_time')->first();
                break;
            case "review":
                return self::where('topic_num', $topicnum)
                    ->where('camp_num', '=', $campnum)
                    ->where('objector_nick_id', '=', NULL)
                    ->latest('submit_time')->first();
                break;
            case "bydate":
                $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
                return self::where('topic_num', $topicnum)
                    ->where('camp_num', '=', $campnum)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', $asofdate)
                    ->latest('submit_time')->first();
                break;
            default:
                return self::where('topic_num', $topicnum)
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
}
