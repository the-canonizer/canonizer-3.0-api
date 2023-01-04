<?php

namespace App\Http\Controllers;

use App\Models\MetaTag;
use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Http\Request\Validate;
use App\Helpers\{ResourceInterface, ResponseInterface};
use App\Http\Request\{ValidationRules, ValidationMessages};
use App\Library\wiki_parser\wikiParser;
use App\Models\Camp;
use App\Models\Nickname;
use App\Models\Statement;
use App\Models\Thread;
use App\Models\Topic;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;

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

            $metaTag = (new MetaTag())->select('id', 'page_name', 'title', 'description', 'submitter_nick_id as author', 'is_static')
                ->where([
                    'page_name' => $page_name,
                ])->first();

            if ($metaTag && $metaTag->is_static == 1) {

                unset($metaTag->is_static);
                unset($metaTag->id);

                $metaTag->author = "";

                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'),  $metaTag, '');
            } else {

                switch ($page_name) {

                    case "CampForumPage":
                        $validationErrors = $validate->validate($request, $this->rules->getMetaTagsByTopicCampForumValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
                        if ($validationErrors) {
                            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                        }

                        $topic_num = $request->keys['topic_num'];
                        $camp_num = $request->keys['camp_num'];
                        $forum_num = $request->keys['forum_num'];

                        $topic = $this->getTopicById($topic_num);
                        
                        $camp = $this->getCampById($topic_num, $camp_num);
                        if (is_null($camp)) {
                            throw new Exception(trans('message.error.record_not_found'));
                        }
                        
                        $forum_num = (new Thread())->select('id', 'title', 'body', 'user_id')->find($forum_num);

                        $submitterNick = $this->getSubmitterById($forum_num->user_id);

                        $title = $camp->camp_name ?? "";
                        $title .= (strlen($title) > 0 ? ' | ' : '') . $metaTag->title;

                        $responseArr = [
                            "page_name" => $page_name ?? "",
                            "title" => $title,
                            "description" => $forum_num->body ?? "",
                            "author" => $submitterNick->nick_name ?? "",
                        ];
                        break;

                    default:
                        $validationErrors = $validate->validate($request, $this->rules->getMetaTagsByTopicCampValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
                        if ($validationErrors) {
                            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                        }

                        $topic_num = $request->keys['topic_num'];
                        $camp_num = $request->keys['camp_num'];

                        $topic = $this->getTopicById($topic_num);
                         
                        $camp = $this->getCampById($topic_num, $camp_num);
                        if (is_null($camp)) {
                            throw new Exception(trans('message.error.record_not_found'), 404);
                        }

                        $statement = $this->getCampStatementById($topic_num, $camp_num);
                        $submitterNick = $this->getSubmitterById($topic->submitter_nick_id);

                        $title = '';
                        switch ($page_name) {
                            case 'TopicDetailsPage':
                                $title = $topic->topic_name ?? "";
                                $title .= (strlen($title) > 0 ? ' | ' : '') . $camp->camp_name;
                                break;

                            case 'TopicHistoryPage':
                                $title = $topic->topic_name ?? "";
                                break;

                            case 'CampHistoryPage':
                            case 'CampForumListPage':
                                $title = $camp->camp_name ?? "";
                                break;

                            default:
                                # code...
                                break;
                        }

                        $title .= (strlen($title) > 0 ? ' | ' : '') . $metaTag->title;

                        $responseArr = [
                            "page_name" => $page_name ?? "",
                            "title" => $title,
                            "description" => $statement,
                            "author" => $submitterNick->nick_name ?? "",
                        ];

                        break;
                }

                if (!$metaTag) {
                    return $this->resProvider->apiJsonResponse(404, trans('message.error.record_not_found'), '', '');
                }
                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $responseArr, '');
            }
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(500, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    private function getTopicById($topic_num)
    {
        $topic = (new Topic())->select('topic_name', 'note', 'submitter_nick_id')
            ->where([
                'topic_num' => $topic_num,
                'objector_nick_id' => null,
                'grace_period' => '0'
            ])->where('go_live_time', '<=', Carbon::now()->timestamp)
            ->orderBy('submit_time', 'desc')->first();

        return $topic;
    }

    private function getCampById($topic_num, $camp_num)
    {
        $camp = (new Camp())->select('id', 'camp_name')
            ->where([
                'topic_num' => $topic_num,
                'camp_num' => $camp_num,
                'objector_nick_id' => null,
                'grace_period' => '0'
            ])
            ->where('go_live_time', '<=', Carbon::now()->timestamp)
            ->orderBy('submit_time', 'desc')->first();


        return $camp;
    }

    private function getCampStatementById($topic_num, $camp_num)
    {
        $campStatement = (new Statement())->select('id', 'value')
            ->where([
                'topic_num' => $topic_num,
                'camp_num' => $camp_num,
                'objector_nick_id' => null,
                'grace_period' => '0'
            ])->where('go_live_time', '<=', Carbon::now()->timestamp)
            ->orderBy('submit_time', 'desc')->first();

        $campStatement = (new wikiParser())->parse($campStatement->value ?? "");
        $campStatement = preg_replace('/[^a-zA-Z0-9_ %\.\?%&-]/s', '', strip_tags($campStatement));
        $campStatement = Str::of($campStatement)->trim()->limit(160);

        return $campStatement;
    }

    private function getSubmitterById($submitter_nick_id)
    {
        $submitterNick = (new Nickname())->select('nick_name')->find($submitter_nick_id);

        return $submitterNick;
    }
}
