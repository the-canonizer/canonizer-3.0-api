<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        if ((isset($filter) && $filter == "default")) {

            return self::where('topic_num', $topicnum)
                ->where('camp_num', '=', $campnum)
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', time())
                ->latest('submit_time')->first();
        } else  if ((isset($filter) && $filter == "review")) {

            return self::where('topic_num', $topicnum)
                ->where('camp_num', '=', $campnum)
                ->where('objector_nick_id', '=', NULL)
                ->latest('submit_time')->first();
        } else if ((isset($filter) && $filter == "bydate")) {
            $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));

            return self::where('topic_num', $topicnum)
                ->where('camp_num', '=', $campnum)
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', $asofdate)
                ->latest('submit_time')->first();
        } else {
            return self::where('topic_num', $topicnum)
                ->where('objector_nick_id', '=', NULL)
                ->latest('submit_time')->first();
        }
    }
}
