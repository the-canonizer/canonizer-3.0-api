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
use App\Library\wiki_parser\wikiParser as wikiParser;

class Topic extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, HasApiTokens, Authorizable, HasFactory;

    protected $table = 'topic';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_name','is_disabled','is_one_level', 'namespace_id', 'submit_time', 'submitter_nick_id', 'go_live_time', 'language', 'note', 'grace_period', 'topic_num'];

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

    public function objectorNickName()
    {
        return $this->hasOne('App\Models\Nickname', 'id', 'objector_nick_id');
    }

    public function nameSpace()
    {
        return $this->hasOne('App\Models\Namespaces', 'id', 'namespace_id');
    }

    public static function getLiveTopic($topicNum, $filter = array(), $asofdate = null)
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
                    ->where('grace_period', 0) 
                    ->latest('submit_time')->first();
                break;
            case "bydate":
                $asOfDate = strtotime(date('Y-m-d H:i:s', strtotime($asofdate)));
                return self::where('topic_num', $topicNum)
                    ->where('go_live_time', '<=', $asOfDate)
                    ->latest('go_live_time')->first();
                break;
            default:
                return self::where('topic_num', $topicNum)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', time())
                    ->latest('submit_time')->first();
        }
    }

    public static function topicLink($topicNum, $campNum = 1, $title, $campName = 'Agreement')
    {
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" . $campName;
        $queryString = (app('request')->getQueryString()) ? '?' . app('request')->getQueryString() : "";
        return $link = config('global.APP_URL_FRONT_END') . ('/topic/' . $topicId . '/' . $campId);
    }

    public static function getTopicHistory($filter, $request, $topicHistoryQuery )
    {
        $liveTopic = Topic::getLiveTopic($filter['topicNum'],'default');

        $topicHistoryQuery->when($filter['type'] == "old", function ($q) use ($filter, $liveTopic) {
                $q->where('go_live_time', '<=', $filter['currentTime'])
                ->where('objector_nick_id', NULL)
                ->where('id', '!=', $liveTopic->id)
                ->where('submit_time', '<', $filter['currentTime']);
        });

        $topicHistoryQuery->when($filter['type'] == "live", function ($q) use ($liveTopic) {
                $q->where('id', $liveTopic->id);
        });

         $topicHistoryQuery->when($filter['type'] == "in_review", function ($q) use ($filter) {
                $q->where('go_live_time', '>', $filter['currentTime'])
                ->where('objector_nick_id' , NULL)
                ->where('submit_time', '<=', $filter['currentTime']);
        });

        $topicHistoryQuery->when($filter['type'] == "objected", function ($q) {
            $q->where('objector_nick_id', '!=', NULL);
        });

        $topicHistoryQuery->when($filter['type'] == "all", function ($q) use ($filter) {
            $q->where('submit_time', '<=', $filter['currentTime']);
        });

        $response = Util::getPaginatorResponse($topicHistoryQuery->paginate($filter['per_page']));
        $response = self::filterTopicHistory($response, $filter, $liveTopic, $request);
        return $response;
    }


    public static function filterTopicHistory($response, $filter, $liveTopic, $request)
    {
        $topicHistory = [];
        if (isset($response->items) && count($response->items) > 0) {
            $nickNameIds = isset($request->user()->id) ? Nickname::getNicknamesIdsByUserId($request->user()->id) : [];
            foreach ($response->items as $val) {
                $submitterUserID = Nickname::getUserIDByNickNameId($val->submitter_nick_id);
                $submittime = $val->submit_time;
                $starttime = time();
                $endtime = $submittime + 60 * 60;
                $interval = $endtime - $starttime;
                $val->objector_nick_name = null;
                $namespace = Namespaces::find($val->namespace_id);
                $namespaceLabel = '';
                if (!empty($namespace)) {
                    $namespaceLabel = Namespaces::getNamespaceLabel($namespace, $namespace->name);
                }
                $val->namespace = $namespaceLabel;
                $val->unsetRelation('nameSpace');
                $val->submitter_nick_name=NickName::getNickName($val->submitter_nick_id)->nick_name;
                $val->isAuthor = (isset($request->user()->id) && $submitterUserID == $request->user()->id) ?  true : false ;
                $val->agreed_to_change = 0;
                switch ($val) {
                    case $val->objector_nick_id !== NULL:
                        $val->status = "objected";
                        $val->objector_nick_name = $val->objectorNickName->nick_name;
                        $val->unsetRelation('objectorNickName');
                        break;
                    case $filter['currentTime'] < $val->go_live_time && $filter['currentTime'] >= $val->submit_time:
                        $val->agreed_to_change = (int) ChangeAgreeLog::whereIn('nick_name_id', $nickNameIds)
                        ->where('change_for', '=', 'topic')
                        ->where('change_id', '=', $val->id)
                        ->exists(); 
                        $val->status = "in_review";
                        break;
                    case $liveTopic->id == $val->id && $filter['type'] != "old":
                        $val->status = "live";
                        break;
                    default:
                        $val->status = "old";
                }
                if (($interval > 0 && $val->grace_period > 0)  && (( isset($request->user()->id) && $request->user()->id != $submitterUserID ) || !isset($request->user()->id)) ) {
                    continue;
                } else {
                    $WikiParser = new wikiParser;
                    $val->parsed_value = $WikiParser->parse($val->value);
                    array_push($topicHistory, $val);
                }
            }
            $response->items = $topicHistory;
        }
        return  $response;
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
