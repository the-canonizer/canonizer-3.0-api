<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Camp;
use App\Models\Topic;
use App\Models\MetaTag;
use App\Models\Nickname;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Cache;
use App\Helpers\{ResourceInterface, ResponseInterface};
use App\Http\Request\{ValidationRules, ValidationMessages};
use App\Models\Statement;
use App\Models\Video;

class MetaTagController extends Controller
{
    protected $rules;
    protected $validationMessages;
    protected $resourceProvider;
    protected $resProvider;

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\Post(path="/meta-tagst",
     *   tags={"MetaTag"},
     *   summary="Get meta tags",
     *   description="This API is used to get meta tags.",
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *              @OA\Property(
     *                  property="page_name",
     *                  description="Page Name is required",
     *                  required=true,
     *                  type="string",
     *              ), 
     *              @OA\Keys(
     *                  @OA\Property(
     *                      property="topic_num",
     *                      description="Topic Number is required",
     *                      required=false,
     *                      type="integer",
     *                  ),
     *                  @OA\Property(
     *                      property="camp_num",
     *                      description="Camp Number is required",
     *                      required=false,
     *                      type="integer",
     *                  ),
     *                  @OA\Property(
     *                      property="forum_num",
     *                      description="Forum Number is required conditionally for page name CampForumPostPage",
     *                      required=false,
     *                      type="integer",
     *                  ),
     *               ) 
     *            )  
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function getMetaTags(Request $request, Validate $validate)
    {
        try {

            $validationErrors = $validate->validate($request, $this->rules->getMetaTagsValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
            if ($validationErrors) {
                return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
            }

            $page_name = (string)Str::of($request->post('page_name'))->trim();

            $cacheKey = 'meta_tags-' . $page_name;
            $metaTag = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($page_name) {
                return (new MetaTag())->select('id', 'page_name', 'title', 'description', 'submitter_nick_id as author', 'is_static')
                    ->where([
                        'page_name' => $page_name,
                    ])->first();
            });

            if (!$metaTag) {
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.record_not_found'));
            }

            $submitterNick = null;

            if (in_array($page_name, ['TopicDetailsPage', 'TopicHistoryPage', 'CampHistoryPage', 'CampForumListPage', 'CampForumPage', 'TopicAnimationPage'])) {

                $validationErrors = $validate->validate($request, $this->rules->getMetaTagsByTopicCampValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
                if ($validationErrors) {
                    return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                }

                $topic_num = intval($request->keys['topic_num']);
                $camp_num = intval($request->keys['camp_num']);

                $topic = $this->getTopicById($topic_num);
                if (is_null($topic)) {
                    return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.topic_not_found'));
                }

                $camp = $this->getCampById($topic_num, $camp_num);
                if (is_null($camp)) {
                    return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.camp_not_found'));
                }

                $submitterNick = $this->getSubmitterById($camp->submitter_nick_id);

                $metaTag = $this->replaceTopicName($metaTag, $topic);
                $metaTag = $this->replaceCampName($metaTag, $camp);
                $metaTag = $this->replaceTopicDescription($metaTag, $camp);
                $metaTag = $this->replaceCampDescription($metaTag, $camp);
            } elseif (in_array($page_name, ['VideosPage'])) {
                $validationErrors = $validate->validate($request, $this->rules->getMetaTagsVideoValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
                if ($validationErrors) {
                    return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                }

                $video = Video::where('id', $request->keys['video_id'])->first();

                if (is_null($video)) {
                    return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.record_not_found'));
                }

                $metaTag = $this->replaceVideoName($metaTag, $video);
                $metaTag = $this->replaceVideoNameInDescription($metaTag, $video);
            } elseif (in_array($page_name, ['SearchResultsPage'])) {

                $validationErrors = $validate->validate($request, $this->rules->getMetaTagsKeywordsValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
                if ($validationErrors) {
                    return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                }

                $metaTag = $this->replaceKeywordsInTitle($metaTag, $request->keys['keywords']);
            }

            $responseArr = [
                "page_name" => $page_name ?? "",
                "title" => $metaTag->title ?? "",
                "description" => $metaTag->description ?? "",
                "author" => $submitterNick->nick_name ?? "",
            ];

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $responseArr, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse($e->getCode() > 0 ? $e->getCode() : 500, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    public function replaceTopicName($metaTag, $topic)
    {
        $metaTag->title = Str::of($metaTag->title)->replace('[topic_name]', $topic->topic_name);
        return $metaTag;
    }

    public function replaceCampName($metaTag, $camp)
    {
        $metaTag->title = Str::of($metaTag->title)->replace('[camp_name]', $camp->camp_name);
        return $metaTag;
    }

    public function replaceTopicDescription($metaTag, $camp)
    {
        $statement = Statement::getLiveStatement([
            'topicNum' => $camp->topic_num,
            'campNum' => $camp->camp_num,
            'asOf' => 'default',
            'asOfDate' => Carbon::now()->timestamp
        ]);

        if (str_contains($metaTag->description, '[topic_description]')) {
            $metaTag->description = Str::of($statement ? strip_tags($statement->value) : '')->limit(160);
        }
        return $metaTag;
    }

    public function replaceCampDescription($metaTag, $camp)
    {
        $statement = Statement::getLiveStatement([
            'topicNum' => $camp->topic_num,
            'campNum' => $camp->camp_num,
            'asOf' => 'default',
            'asOfDate' => Carbon::now()->timestamp
        ]);

        if (str_contains($metaTag->description, '[camp_description]')) {
            $metaTag->description = Str::of($statement ? strip_tags($statement->value) : '')->limit(160);
        }
        return $metaTag;
    }

    public function replaceVideoName($metaTag, $video)
    {
        $metaTag->title = Str::of($metaTag->title)->replace('[video_name]', $video->title);
        return $metaTag;
    }

    public function replaceVideoNameInDescription($metaTag, $video)
    {
        $metaTag->description = Str::of($metaTag->description)->replace('[video_name]', $video->title);
        return $metaTag;
    }

    public function replaceKeywordsInTitle($metaTag, $keywords)
    {
        $keywords = explode('+', $keywords);
        $metaTag->title = Str::of($metaTag->title)->replace('[keywords]', implode(',', $keywords));
        return $metaTag;
    }

    private function getTopicById($topic_num)
    {
        $cacheKey = 'live_topic_default-' . $topic_num;
        $topic = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($topic_num) {
            return (new Topic())->where([
                'topic_num' => $topic_num,
                'objector_nick_id' => null,
                'grace_period' => '0'
            ])->where('go_live_time', '<=', Carbon::now()->timestamp)
                ->orderBy('submit_time', 'desc')->first();
        });
        return $topic;
    }

    private function getCampById($topic_num, $camp_num)
    {
        $cacheKey = 'live_camp_default-' . $topic_num . '-' . $camp_num;
        $camp = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($topic_num, $camp_num) {
            return (new Camp())->where([
                'topic_num' => $topic_num,
                'camp_num' => $camp_num,
                'objector_nick_id' => null,
                'grace_period' => '0'
            ])
                ->where('go_live_time', '<=', Carbon::now()->timestamp)
                ->orderBy('submit_time', 'desc')->first();
        });
        return $camp;
    }

    private function getSubmitterById($submitter_nick_id)
    {
        $cacheKey = 'get_submitter_by_id-' . $submitter_nick_id;
        $submitterNick = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($submitter_nick_id) {
            return (new Nickname())->select('nick_name')->find($submitter_nick_id);
        });
        return $submitterNick;
    }
}
