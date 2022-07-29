<?php

namespace App\Models;

use App\Models\Camp;
use App\Facades\Util;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Topic extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, HasApiTokens  , Authorizable, HasFactory;
    
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
           // dd($model);
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

    public static function getLiveTopic($topicNum, $filter = array())
    {
        switch ($filter) {
            case "default":
                return self::where('topic_num', $topicNum)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', time())
                    ->latest('submit_time')->first();
                break;
            case "review":
                return self::where('topic_num', $topicNum)
                    ->where('objector_nick_id', '=', NULL)
                    ->latest('submit_time')->first();
                break;
            case "bydate":
                $asOfDate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asofdate'])));
                return self::where('topic_num', $topicNum)
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


    public static function topicLink($topicNum, $campNum = 1, $title, $campName = 'Aggreement')
    {
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" . $campName;
        $queryString = (app('request')->getQueryString()) ? '?' . app('request')->getQueryString() : "";
        return $link = config('global.APP_URL_FRONT_END').('/topic/' . $topicId . '/' . $campId);
    }

    public static function ifTopicNameAlreadyTaken($data)
    {
        $liveTopicData = self::select('topic.*')
        ->join('camp','camp.topic_num','=','topic.topic_num')
        ->where('camp.camp_name','=','Agreement')
        ->where('topic.topic_name', $data['topic_name'])
        ->where('topic.objector_nick_id',"=",null)
        ->whereRaw('topic.go_live_time in (select max(go_live_time) from topic where objector_nick_id is null and go_live_time < "' . time() . '" group by topic_num)')
        ->where('topic.go_live_time', '<=', time())                            
        ->latest('submit_time')
        ->first();

        $nonLiveTopicData = self::select('topic.*')
        ->join('camp','camp.topic_num','=','topic.topic_num')
        ->where('camp.camp_name','=','Agreement')
        ->where('topic_name', $data['topic_name'])
        ->where('topic.objector_nick_id',"=",null)
        ->where('topic.go_live_time',">",time())
        ->first();

        return (isset($liveTopicData) && $liveTopicData->topic_num != $data['topic_num']) || (isset($nonLiveTopicData) && $nonLiveTopicData->topic_num != $data['topic_num']);
    }


}
