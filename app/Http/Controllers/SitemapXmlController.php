<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Statement;
use Illuminate\Http\Request;

class SitemapXmlController extends Controller
{

    public function index(Request $request)
    {
        $response = [];
        $response['index'] = $this->getIndexSiteMap();
        $response['sitemap_home.xml'] = $this->getHomeSiteMapUrls();
        $response['sitemap_topic.xml'] = $this->getTopicSiteMapUrls();
        $response['sitemap_camp.xml'] = $this->getCampSiteMapUrls();
        $response['sitemap_statement.xml'] = $this->getStatementSiteMapUrls();
        $response['sitemap_thread.xml'] = $this->getThreadSiteMapUrls();
        $response['sitemap_post.xml'] = $this->getPostSiteMapUrls();
        $status = 200;
        $message = trans('message.success.success');
        return $this->resProvider->apiJsonResponse($status, $message, $response, null);
    }

    public function getIndexSiteMap()
    {
        return $res['items'] = [
            [
                'url' => 'sitemap_home.xml',
                'file_name' => 'sitemap_home.xml',
                'last_modified' => Carbon::now()
            ],
            [
                'url' => 'sitemap_topic.xml',
                'Last Modified' => Carbon::now()
            ],
            [
                'url' => 'sitemap_camp.xml',
                'Last Modified' => Carbon::now()
            ],
            [
                'url' => 'sitemap_statement.xml',
                'Last Modified' => Carbon::now()
            ],
            [
                'url' => 'sitemap_thread.xml',
                'Last Modified' => Carbon::now()
            ],
            [
                'url' => 'sitemap_post.xml',
                'Last Modified' => Carbon::now()
            ]
        ];
    }
    public function getHomeSiteMapUrls()
    {
        return   $response['items'] = [
            [
                'url' =>  env('APP_URL_FRONT_END') . '/create/topic',
                'last_modified' => Carbon::now()
            ],
            [
                'url' =>   env('APP_URL_FRONT_END') . '/settings?tab=profile_info',
                'last_modified' => Carbon::now()
            ],
            [
                'url' =>   env('APP_URL_FRONT_END') . '/settings?tab=social_oauth_verification',
                'last_modified' => Carbon::now()
            ],
            [
                'url' =>    env('APP_URL_FRONT_END') . '/settings?tab=change_password',
                'last_modified' => Carbon::now()
            ],
            [
                'url' =>  env('APP_URL_FRONT_END') . '/settings?tab=nick_name',
                'last_modified' => Carbon::now()
            ],
            [
                'url' =>  env('APP_URL_FRONT_END') . '/settings?tab=supported_camps',
                'last_modified' => Carbon::now()
            ],
            [
                'url' => env('APP_URL_FRONT_END') . '/settings?tab=subscriptions',
                'Last Modified' => Carbon::now()
            ]
        ];
    }
    public function getTopicSiteMapUrls()
    {

        $topic =  Topic::where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->latest('submit_time')->get();
        $topicUrl = [];
        foreach ($topic as $tv) {
            $filter['topicNum'] = $tv->topic_num;
            $filter['asOf'] = $tv->asof;
            $filter['campNum'] = 1;
            $camp = Camp::getLiveCamp($filter);
            $topicLink = Util::getTopicCampUrlWithoutTime($tv->topic_num, 1, $tv, $camp, time());
            $topicUrl['items'][] = [
                'url' => $topicLink,
                'last_modified' => Carbon::now()
            ];
            $topicHistoryLink = Util::topicHistoryLink($tv->topic_num, 1, $tv->topic_name, 'Aggreement', 'topic');
            $topicUrl['items'][] = [
                'url' => $topicHistoryLink,
                'last_modified' => Carbon::now()
            ];
        }
        return  $topicUrl;
    }
    public function getCampSiteMapUrls()
    {
        $camps = Camp::where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->latest('go_live_time')->get();
        foreach ($camps as $cv) {
            $topic = Topic::getLiveTopic($cv->topic_num);
            $campLink = Util::getTopicCampUrlWithoutTime($cv->topic_num, $cv->camp_num, $topic, $cv, time());
            $topicUrl['items'][] = [
                'url' => $campLink,
                'last_modified' => Carbon::now()
            ];
            $campHistoryLink = Util::topicHistoryLink($cv->topic_num, $cv->camp_num, $cv->topic_name, $cv->camp_name, 'camp');
            $topicUrl['items'][] = [
                'url' => $campHistoryLink,
                'last_modified' => Carbon::now()
            ];
        }
        return  $topicUrl;
    }
    public function getStatementSiteMapUrls()
    {
        $statements =  Statement::where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->orderBy('submit_time', 'desc')
            ->get();
        foreach ($statements as $sv) {
            $statementsHistoryLink = config('global.APP_URL_FRONT_END') . '/statement/history/' . $sv->topic_num . '/' . $sv->camp_num;
            $topicUrl['items'][] = [
                'url' => $statementsHistoryLink,
                'last_modified' => Carbon::now()
            ];
        }
        return  $topicUrl;
    }
    public function getThreadSiteMapUrls()
    {
        $threads =  Thread::get();
        foreach ($threads as $thread) {
            $topic = Topic::getLiveTopic($thread->topic_id);
            $filter['topicNum'] = $thread->topic_id;
            $filter['asOf'] = $thread->asof;
            $filter['campNum'] = $thread->camp_id;
            $camp = Camp::getLiveCamp($filter);
            $threadLink = config('global.APP_URL_FRONT_END') . '/forum/' . $thread->topic_id . '-' .  Util::replaceSpecialCharacters($topic->topic_name) . '/' . $thread->camp_id . '-' . Util::replaceSpecialCharacters($camp->camp_name) . '/threads/';
            $topicUrl['items'][] = [
                'url' => $threadLink,
                'last_modified' => Carbon::now()
            ];
        }
        return  $topicUrl;
    }
    public function getPostSiteMapUrls()
    {
        $posts = Reply::leftJoin('nick_name', 'nick_name.id', '=', 'post.user_id')
            ->Join('thread as t', 't.id', '=', 'post.c_thread_id')
            ->select('post.*', 't.camp_id', 't.topic_id')
            ->where('is_delete', '0')->latest()->get();
        foreach ($posts as $post) {
            $topic = Topic::getLiveTopic($post->topic_id);
            $filter['topicNum'] = $post->topic_id;
            $filter['asOf'] = $post->asof;
            $filter['campNum'] = $post->camp_id;
            $camp = Camp::getLiveCamp($filter);
            $postLink = config('global.APP_URL_FRONT_END') . '/forum/' . $post->topic_id . '-' .  Util::replaceSpecialCharacters($topic->topic_name) . '/' . $post->camp_id . '-' . Util::replaceSpecialCharacters($camp->camp_name) . '/threads/' . $post->id;
            $topicUrl['items'][] = [
                'url' => $postLink,
                'last_modified' => Carbon::now()
            ];
        }
        return  $topicUrl;
    }
}
