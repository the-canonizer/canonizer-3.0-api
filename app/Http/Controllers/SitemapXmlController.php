<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Namespaces;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Statement;
use Illuminate\Http\Request;

class SitemapXmlController extends Controller
{

    public function index(Request $request)
    {
        $data = [
            'index' => $this->getIndexSiteMap(),
            'sitemap_home.xml' => $this->getHomeSiteMapUrls(),
            'sitemap_topic.xml' => $this->getTopicSiteMapUrls(),
            'sitemap_camp.xml' => $this->getCampSiteMapUrls(),
            'sitemap_thread.xml' => $this->getThreadSiteMapUrls(),
            'sitemap_post.xml' => $this->getPostSiteMapUrls(),
            'sitemap_videos.xml' => $this->getVideoSiteMapUrls(),
        ];
        $status = 200;
        $message = trans('message.success.success');
        return $this->resProvider->apiJsonResponse($status, $message, $data, null);
    }

    private function getIndexSiteMap()
    {
        $urls = [
            'sitemap_home.xml',
            'sitemap_topic.xml',
            'sitemap_camp.xml',
            'sitemap_thread.xml',
            'sitemap_post.xml',
            'sitemap_videos.xml',
        ];
        $lastModified = Carbon::now()->startOfDay()->toIso8601String();
        $siteMaps = array_map(function ($url) use ($lastModified) {
            return [
                'url' => $url,
                'last_modified' => $lastModified,
            ];
        }, $urls);
        return $siteMaps;
    }

    public function getHomeSiteMapUrls()
    {
        $urls = [
            '/',
            '/browse',
            '/activities',
            '/terms-and-services',
            '/privacy-policy',
            'https://blog.canonizer.com/',
            '/files/2012_amplifying_final.pdf',
            '/create/topic',
        ];
        $siteMaps = array_map(function ($url) {
            $isExternal = strpos($url, 'http') === 0;
            $baseUrl = $isExternal ? '' : env('APP_URL_FRONT_END');
            $url = $baseUrl . $url;
            return [
                'url' => $url,
                'last_modified' => Carbon::now()->startOfDay()->toIso8601String()
            ];
        }, $urls);
        return $siteMaps;
    }

    public function getTopicSiteMapUrls()
    {
        $namespaces = Namespaces::where('name', 'like', "%sandbox%")->get();
        $namespaceIds = [];
        foreach ($namespaces as $namespace) {
            $namespaceIds[] = $namespace->id;
        }
        $topics = Topic::whereNull('objector_nick_id')
            ->where('go_live_time', '<=', time())
            ->whereNotIn('namespace_id', $namespaceIds)
            ->latest('submit_time')
            ->get();
        $topicUrls = [];
        $urlTopicSet = [];
        foreach ($topics as $topic) {
            $camp = Camp::getLiveCamp([
                'topicNum' => $topic->topic_num,
                'asOf' => $topic->asof,
                'campNum' => 1,
            ]);
            if ($camp !== null && $camp->is_archive == 0) {
                $topicUrl = Util::getTopicCampUrlWithoutTime($topic->topic_num, 1, $topic, $camp, time());
                if (!in_array($topicUrl, $urlTopicSet)) {
                    $urlTopicSet[] = $topicUrl;
                    $topicUrls[] = [
                        'url' => $topicUrl,
                        'last_modified' => !empty($topic->go_live_time) ? Carbon::createFromTimestamp($topic->go_live_time)->toIso8601String() : Carbon::now()->startOfDay()->toIso8601String()
                    ];
                }
            }
        }

        return $topicUrls;
    }

    public function getCampSiteMapUrls()
    {
        $namespaces = Namespaces::where('name', 'like', "%sandbox%")->get();
        $namespaceIds = [];
        foreach ($namespaces as $namespace) {
            $namespaceIds[] = $namespace->id;
        }
        $camps = Camp::where('objector_nick_id', '=', null)
            ->where('go_live_time', '<=', time())
            ->where('is_archive', '0')
            ->whereHas('topic', function ($query) use ($namespaceIds) {
                $query->whereNotIn('topic.namespace_id', $namespaceIds);
            })
            ->latest('go_live_time')
            ->get();
        $campUrls = [];
        $urlSet = [];
        foreach ($camps as $camp) {
            $topic = Topic::getLiveTopic($camp->topic_num);
            $campLink = Util::getTopicCampUrlWithoutTime($camp->topic_num, $camp->camp_num, $topic, $camp, time());
            if (!in_array($campLink, $urlSet)) {
                $urlSet[] = $campLink;
                $campUrls[] = [
                    'url' => $campLink,
                    'last_modified' => !empty($camp->go_live_time) ? Carbon::createFromTimestamp($camp->go_live_time)->toIso8601String() : Carbon::now()->startOfDay()->toIso8601String()
                ];
            }
        }

        return $campUrls;
    }

    public function getThreadSiteMapUrls()
    {
        $namespaces = Namespaces::where('name', 'like', "%sandbox%")->get();
        $namespaceIds = [];
        foreach ($namespaces as $namespace) {
            $namespaceIds[] = $namespace->id;
        }
        $threads =  Thread::whereHas('topic', function ($query) use ($namespaceIds) {
            $query->whereNotIn('topic.namespace_id', $namespaceIds);
        })->get();
        $unique = [];
        $urlThreadSet = [];
        foreach ($threads as $thread) {
            if (in_array($thread->topic_id . '' . $thread->camp_id, $unique)) {
                continue;
            }
            $unique[] = $thread->topic_id . '' . $thread->camp_id;
            $topic = Topic::getLiveTopic($thread->topic_id);
            $filter['topicNum'] = $thread->topic_id;
            $filter['asOf'] = $thread->asof;
            $filter['campNum'] = $thread->camp_id;
            $camp = Camp::getLiveCamp($filter);
            if (empty($topic) || empty($camp)) {
                continue;
            }
            $threadLink = config('global.APP_URL_FRONT_END') . '/forum/' . $thread->topic_id . '-' .  Util::replaceSpecialCharacters($topic->topic_name) . '/' . $thread->camp_id . '-' . Util::replaceSpecialCharacters($camp->camp_name) . '/threads/';
            if (!in_array($threadLink, $urlThreadSet)) {
                $urlThreadSet[] = $threadLink;
                $topicUrl[] = [
                    'url' => $threadLink,
                    'last_modified' => !empty($thread->updated_at) ? Carbon::createFromTimestamp($thread->updated_at)->toIso8601String() : Carbon::now()->startOfDay()->toIso8601String()
                ];
            }
        }
        return  $topicUrl;
    }

    public function getPostSiteMapUrls()
    {
        $namespaces = Namespaces::where('name', 'like', "%sandbox%")->get();
        $namespaceIds = [];
        foreach ($namespaces as $namespace) {
            $namespaceIds[] = $namespace->id;
        }
        $threadsWithReplies = Thread::has('replies')->with('latestReply')->withCount('replies')
            ->whereHas('topic', function ($query) use ($namespaceIds) {
                $query->whereNotIn('topic.namespace_id', $namespaceIds);
            })
            ->get()->sortByDesc(function ($thread, $key) {
                return $thread->latestReply->updated_at;
            });
        $urlPostSet = [];
        foreach ($threadsWithReplies as $post) {
            if (!empty($post->latestReply)) {
                $topic = Topic::getLiveTopic($post->topic_id);
                $filter['topicNum'] = $post->topic_id;
                $filter['campNum'] = $post->camp_id;
                $camp = Camp::getLiveCamp($filter);
                if (empty($topic) || empty($camp)) {
                    continue;
                }
                $postLink = config('global.APP_URL_FRONT_END') . '/forum/' . $post->topic_id . '-' .  Util::replaceSpecialCharacters($topic->topic_name) . '/' . $post->camp_id . '-' . Util::replaceSpecialCharacters($camp->camp_name) . '/threads/' . $post->id;
                if (!in_array($postLink, $urlPostSet)) {
                    $topicUrl[] = [
                        'url' => $postLink,
                        'last_modified' => !empty($post->latestReply->updated_at) ? Carbon::createFromTimestamp($post->latestReply->updated_at)->toIso8601String() : Carbon::now()->startOfDay()->toIso8601String()
                    ];
                }
            }
        }
        return  $topicUrl;
    }

    public function getVideoSiteMapUrls()
    {
        $urls = [
            '/introduction',
            '/perceiving-a-strawberry',
            '/differentiating-reality-and-knowledge-of-reality',
            '/the-world-in-your-head',
            '/the-perception-of-size',
            '/computational-binding',
            '/cognitive-knowledge',
            '/simulation-hypothesis',
            '/representational-qualia-theory-consensus',
            '/conclusion',
        ];
        $siteMaps = array_map(function ($url) {
            $isExternal = strpos($url, 'http') === 0;
            $baseUrl = $isExternal ? '' : env('APP_URL_FRONT_END') . '/videos/consciousness';
            $url = $baseUrl . $url;
            return [
                'url' => $url,
                'last_modified' => Carbon::now()->startOfDay()->toIso8601String()
            ];
        }, $urls);
        return $siteMaps;
    }
}
