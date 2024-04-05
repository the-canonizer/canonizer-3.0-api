<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Camp;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\MetaTag;
use App\Models\Nickname;
use App\Models\Statement;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Cache;
use App\Library\wiki_parser\wikiParser;
use App\Helpers\{ResourceInterface, ResponseInterface};
use App\Http\Request\{ValidationRules, ValidationMessages};

class MetaTagController extends Controller
{
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

            if ($metaTag && $metaTag->is_static == 1) {

                unset($metaTag->is_static);
                unset($metaTag->id);

                $metaTag->author = "";

                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'),  $metaTag, '');
            } else {

                $validationErrors = $validate->validate($request, $this->rules->getMetaTagsByTopicCampValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
                if ($validationErrors) {
                    return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                }

                $topic_num = $request->keys['topic_num'];
                $camp_num = $request->keys['camp_num'];
                // $forum_num = $request->keys['forum_num'];

                $topic = $this->getTopicById($topic_num);
                if (is_null($topic)) {
                    return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.topic_not_found'));
                }

                $camp = $this->getCampById($topic_num, $camp_num);
                if (is_null($camp)) {
                    return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.camp_not_found'));
                }
                $description = $this->getCampStatementById($topic_num, $camp_num);
                $submitterNick = $this->getSubmitterById($topic->submitter_nick_id);
                $custom = false;

                switch ($page_name) {

                    case 'TopicDetailsPage':
                    case 'TopicAnimationPage':
                        $title = $topic->topic_name ?? "";
                        $title .= (strlen($title) > 0 ? ' | ' : '') . $camp->camp_name;
                        break;

                    case "CampForumListPage":
                        $custom = true;
                        $title = $metaTag->title ?? "";
                        $title .= (strlen($title) > 0 ? ' | ' : '') . $camp->camp_name;

                        $description = $metaTag->description ?? "";
                        $description .= (strlen($description) > 0 ? ' - ' : '') . $camp->camp_name;

                        break;

                    case 'TopicHistoryPage':
                        $title = $topic->topic_name ?? "";
                        break;

                    case 'CampHistoryPage':
                        $title = $camp->camp_name ?? "";
                        break;

                    case 'CampForumPage':
                        $title = $camp->camp_name ?? "";
                        $description = $metaTag->description ?? "";
                        break;

                    default:
                        break;
                }

                if (!$custom) {
                    $title .= (strlen($title) > 0 ? ' | ' : '') . $metaTag->title;
                }
                $responseArr = [
                    "page_name" => $page_name ?? "",
                    "title" => $title ?? "",
                    "description" => $description ?? "",
                    "author" => $submitterNick->nick_name ?? "",
                ];

                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $responseArr, '');
            }
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse($e->getCode() > 0 ? $e->getCode() : 500, trans('message.error.exception'), '', $e->getMessage());
        }
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

    private function getCampStatementById($topic_num, $camp_num)
    {
        $cacheKey = 'live_statement_default-' . $topic_num . '-' . $camp_num;
        $campStatement = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($topic_num, $camp_num) {
            $campStatement = (new Statement())->where([
                    'topic_num' => $topic_num,
                    'camp_num' => $camp_num,
                    'objector_nick_id' => null,
                    'grace_period' => '0'
                ])->where('go_live_time', '<=', Carbon::now()->timestamp)
                ->orderBy('submit_time', 'desc')->first();

            return $campStatement;
        });
        $campStatement = (new wikiParser())->parse($campStatement->value ?? "");
        $campStatement = preg_replace('/[^a-zA-Z0-9_ %\.\?%&-]/s', '', strip_tags($campStatement));
        $campStatement = Str::of($campStatement)->trim()->limit(160);
        return $campStatement;
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
