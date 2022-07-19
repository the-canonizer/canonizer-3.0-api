<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Library\wiki_parser\wikiParser as wikiParser;
use App\Models\Nickname;
use App\Facades\Util;
use App\Models\User;
use Throwable;
use App\Jobs\PurposedToSupportersMailJob;


class Statement extends Model
{
    protected $table = 'statement';
    public $timestamps = false;
    public static function getLiveStatement($filter = array())
    {
        $filterName = $filter['asOf'];
        if (!$filterName) {
            $filter['asOf'] = 'default';
        }
        return self::liveStatementAsOfFilter($filter);
    }

    public static function defaultAsOfFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    public static function reviewAsofFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    public static function byDateFilter($filter)
    {
        $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate'])));
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', $asofdate)
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    private function liveStatementAsOfFilter($filter)
    {
        $asOfFilter = [
            'default' => self::defaultAsOfFilter($filter),
            'review'  => self::reviewAsofFilter($filter),
            'bydate'  => self::byDateFilter($filter),
        ];
        return $asOfFilter[$filter['asOf']];
    }

    public static function checkIfFileInUse($shortCode = '')
    {
        if ($shortCode) {
            $result = self::where('value', 'like', '%' . $shortCode . '%')->count();
            return ($result > 0) ? true : false;
        }
        return false;
    }

    public function objectorNickName()
    {
        return $this->hasOne('App\Models\Nickname', 'id', 'objector_nick_id');
    }

    public function submitterNickName()
    {
        return $this->hasOne('App\Models\Nickname', 'id', 'submitter_nick_id');
    }

    public static function statementHistory($statement_query, $response, $filter, $campLiveStatement, $request)
    {
        $statement_query->when($filter['type'] == "objected", function ($q) {
            $q->where('objector_nick_id', '!=', NULL);
        });

        $statement_query->when($filter['type'] == "in_review" && $request, function ($q) use ($filter) {
            $q->where('go_live_time', '>', $filter['currentTime'])
                ->where('submit_time', '<=', $filter['currentTime']);
        });

        $statement_query->when($filter['type'] == "old", function ($q) use ($filter,  $campLiveStatement) {
            $q->where('go_live_time', '<=', $filter['currentTime'])
                ->where('objector_nick_id', NULL)
                ->where('id', '!=', $campLiveStatement->id)
                ->where('submit_time', '<=', $filter['currentTime']);
        });

        $statement_query->when($filter['type'] == "all" && !$request, function ($q) use ($filter) {
            $q->where('go_live_time', '<=', $filter['currentTime']);
        });

        $response->statement = Util::getPaginatorResponse($statement_query->paginate($filter['per_page']));
        $response = self::filterStatementHistory($response, $filter, $request, $campLiveStatement);
        return $response;
    }

    public static function filterStatementHistory($response, $filter, $request, $campLiveStatement)
    {
        $data = $response->statement;
        unset($response->statement);
        $data->details = $response;
        $statementHistory = [];
        if (isset($data->items) && count($data->items) > 0) {
            foreach ($data->items as $val) {
                $submitterUserID = Nickname::getUserIDByNickNameId($val->submitter_nick_id);
                $submittime = $val->submit_time;
                $starttime = time();
                $endtime = $submittime + 60 * 60;
                $interval = $endtime - $starttime;
                $val->objector_nick_name = null;
                $val->submitter_nick_name=NickName::getNickName($val->submitter_nick_id)->nick_name;
                $val->isAuthor = (isset($request->user()->id) && $submitterUserID == $request->user()->id) ?  true : false ;
                switch ($val) {
                    case $val->objector_nick_id !== NULL:
                        $val->status = "objected";
                        $val->objector_nick_name = $val->objectorNickName->nick_name;
                        $val->unsetRelation('objectorNickName');
                        break;
                    case $filter['currentTime'] < $val->go_live_time && $filter['currentTime'] >= $val->submit_time:
                        $val->status = "in_review";
                        break;
                    case $campLiveStatement->id == $val->id && $filter['type'] != "old":
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
                    array_push($statementHistory, $val);
                }
            }
            $data->items = $statementHistory;
            return  $data;
        }
    }

    public static function mailSubscribersAndSupporters($directSupporter, $subscribers, $link, $dataObject)
    {
        $alreadyMailed = [];
        if (!empty($directSupporter)) {
            foreach ($directSupporter as $supporter) {
                $supportData = $dataObject;
                $user = Nickname::getUserByNickName($supporter->nick_name_id);
                $alreadyMailed[] = $user->id;
                $topic = Topic::where('topic_num', '=', $supportData['topic_num'])->latest('submit_time')->get();
                $topic_name_space_id = isset($topic[0]) ? $topic[0]->namespace_id : 1;
                $nickName = Nickname::find($supporter->nick_name_id);
                $supported_camp = $nickName->getSupportCampList($topic_name_space_id, ['nofilter' => true]);
                $supported_camp_list = $nickName->getSupportCampListNamesEmail($supported_camp, $supportData['topic_num'], $supportData['camp_num']);
                $supportData['support_list'] = $supported_camp_list;
                $ifalsoSubscriber = Camp::checkifSubscriber($subscribers, $user);
                $data['namespace_id'] =  $topic_name_space_id;
                if ($ifalsoSubscriber) {
                    $supportData['also_subscriber'] = 1;
                    $supportData['sub_support_list'] = Camp::getSubscriptionList($user->id, $supportData['topic_num'], $supportData['camp_num']);
                }
                $receiver = env('APP_ENV') == "production" ? $user->email : env('ADMIN_EMAIL');
                try {
                    dispatch(new PurposedToSupportersMailJob($user, $link, $supportData,$receiver))->onQueue(env('QUEUE_SERVICE_NAME'));
                } catch (Throwable $e) {
                    echo  $e->getMessage();
                }
            }
        }
        if (!empty($subscribers)) {
            foreach ($subscribers as $usr) {
                $subscriberData = $dataObject;
                $userSub = User::find($usr);
                if (!in_array($userSub->id, $alreadyMailed, TRUE)) {
                    $alreadyMailed[] = $userSub->id;
                    $subscriptions_list = Camp::getSubscriptionList($userSub->id, $subscriberData['topic_num'], $subscriberData['camp_num']);
                    $subscriberData['support_list'] = $subscriptions_list;
                    $receiver = env('APP_ENV') == "production" ? $user->email : env('ADMIN_EMAIL');
                    $subscriberData['subscriber'] = 1;
                    $topic = Topic::getLiveTopic($subscriberData['topic_num']);
                    $data['namespace_id'] = $topic->namespace_id;
                    try {
                        dispatch(new PurposedToSupportersMailJob($userSub, $link, $subscriberData,$receiver))->onQueue(env('QUEUE_SERVICE_NAME'));
                    } catch (Throwable $e) {
                        echo  $e->getMessage();
                    }
                }
            }
        }
        return;
    }
}
