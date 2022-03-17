<?php

namespace App\Models;

use App\Models\Camp;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;

class Topic extends Model
{
    protected $table = 'topic';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_name', 'namespace_id', 'submit_time', 'submitter_nick_id', 'go_live_time', 'language', 'note', 'grace_period', 'topic_num'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public static function boot()
    {
        static::created(function ($model) {
            ## while creating topic for very first time ##
            ## this will not run when updating ##
            if ($model->topic_num == '' || $model->topic_num == null) {
                $nextTopicNum = DB::table('topic')->max('topic_num');
                $nextTopicNum++;
                $model->topic_num = $nextTopicNum;
                $model->update();

                ## create agreement ##
                $camp = new Camp();
                $camp->topic_num = $model->topic_num;
                $camp->parent_camp_num = null;
                $camp->camp_num = 1;
                $camp->key_words = '';
                $camp->language = $model->language;
                $camp->note = $model->note;
                $camp->submit_time = time();
                $camp->submitter_nick_id = $model->submitter_nick_id;
                $camp->go_live_time = $model->go_live_time;
                $camp->title = $model->topic_name;
                $camp->camp_name = Camp::AGREEMENT_CAMP;

                $camp->save();
            }
        });
        parent::boot();
    }

    public static function getLiveTopic($topicnum, $filter = array())
    {
        if (((isset($filter) && $filter == "default"))) {
            return self::where('topic_num', $topicnum)
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', time())
                ->latest('submit_time')->first();
        } else if ((isset($filter) && $filter == "review")) {

            return self::where('topic_num', $topicnum)
                ->where('objector_nick_id', '=', NULL)
                ->latest('submit_time')->first();
        } else if ((isset($filter) && $filter == "bydate")) {
            $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asofdate'])));
            return self::where('topic_num', $topicnum)
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', $asofdate)
                ->latest('submit_time')->first();
        } else {

            return self::where('topic_num', $topicnum)
                ->where('objector_nick_id', '=', NULL)
                ->latest('submit_time')->first();
        }
    }

    public static function topicLink($topicNum, $campNum = 1 , $title, $campName = 'Aggreement'){
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" .$campName;
        $queryString = (app('request')->getQueryString()) ? '?'.app('request')->getQueryString() : "";
        return $link = url('topic/' . $topicId . '/' . $campId);
    }

    
}
