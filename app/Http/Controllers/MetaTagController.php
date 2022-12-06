<?php

namespace App\Http\Controllers;

use App\Models\MetaTag;
use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Http\Request\Validate;
use App\Helpers\{ResourceInterface, ResponseInterface};
use App\Http\Request\{ValidationRules, ValidationMessages};
use App\Models\Camp;
use App\Models\Nickname;
use App\Models\Thread;
use App\Models\Topic;
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

            $metaTag = (new MetaTag())->select('id', 'page_name', 'title', 'description', 'submitter_nick_id as author', 'image_url', 'keywords', 'is_static')
                ->where([
                    'page_name' => $page_name,
                ])->first();

            if ($metaTag && $metaTag->is_static == 1) {

                unset($metaTag->is_static);
                unset($metaTag->id);
                unset($metaTag->image_url);
                $metaTag->author = "";
                $metaTag->keywords = implode('|', (array)$metaTag->keywords);

                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'),  $metaTag, '');
            } else {

                switch ($page_name) {

                    case "TopicHistoryPage":
                        $validationErrors = $validate->validate($request, $this->rules->getMetaTagsByTopicValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
                        if ($validationErrors) {
                            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                        }

                        $topic_num = $request->keys['topic_num'];
                        $camp_num = $request->keys['camp_num'];

                        $topic = (new Topic())->select('topic_name', 'note', 'submitter_nick_id')->find($topic_num);
                        $submitterNick = (new Nickname())->select('nick_name')->find($topic->submitter_nick_id);

                        $responseArr = [
                            "page_name" => $page_name ?? "",
                            "title" => $topic->topic_name ?? "",
                            "description" => $topic->note ?? "",
                            "author" => $submitterNick->nick_name ?? "",
                            // "image_url" => $metaTag->image_url ?? "",
                            "keywords" => "",
                        ];

                        break;

                    case "CampForumPostPage":
                        $validationErrors = $validate->validate($request, $this->rules->getMetaTagsByTopicCampForumValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
                        if ($validationErrors) {
                            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                        }

                        $topic_num = $request->keys['topic_num'];
                        $camp_num = $request->keys['camp_num'];
                        $forum_num = $request->keys['forum_num'];

                        $topic = (new Topic())->select('topic_name', 'note', 'submitter_nick_id')->find($topic_num);

                        $camp = (new Camp())->select('id', 'key_words')->where([
                            'topic_num' => $topic_num,
                            'camp_num' => $camp_num,
                            'objector_nick_id' => null
                        ])->orderBy('submit_time', 'desc')->first();

                        $forum_num = (new Thread())->select('id', 'title', 'body', 'user_id')
                            ->find($forum_num);

                        $submitterNick = (new Nickname())->select('nick_name')->find($forum_num->user_id);

                        $responseArr = [
                            "page_name" => $page_name ?? "",
                            "title" => $forum_num->title ?? "",
                            "description" => $forum_num->body ?? "",
                            "author" => $submitterNick->nick_name ?? "",
                            // "image_url" => $metaTag->image_url ?? "",
                            "keywords" => Str::of($camp->key_words)->replace(',', '|')->replace(' ', ''),
                        ];
                        break;

                    default:
                        $validationErrors = $validate->validate($request, $this->rules->getMetaTagsByTopicCampValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
                        if ($validationErrors) {
                            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                        }

                        $topic_num = $request->keys['topic_num'];
                        $camp_num = $request->keys['camp_num'];

                        $topic = (new Topic())->select('topic_name', 'note', 'submitter_nick_id')->find($topic_num);
                        $submitterNick = (new Nickname())->select('nick_name')->find($topic->submitter_nick_id);
                        $camp = (new Camp())->select('key_words')->where([
                            'topic_num' => $topic_num,
                            'camp_num' => $camp_num,
                            'objector_nick_id' => null
                        ])->orderBy('submit_time', 'desc')->first();

                        $responseArr = [
                            "page_name" => $page_name ?? "",
                            "title" => $topic->topic_name ?? "",
                            "description" => $topic->note ?? "",
                            "author" => $submitterNick->nick_name ?? "",
                            // "image_url" => $metaTag->image_url ?? "",
                            "keywords" => Str::of($camp->key_words ?? '')->replace(',', '|')->replace(' ', ''),
                        ];

                        break;
                }


                if (!$metaTag) {
                    return $this->resProvider->apiJsonResponse(401, trans('message.error.exception'), '', "Page not found");
                }
                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $responseArr, '');
            }
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(500, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}
