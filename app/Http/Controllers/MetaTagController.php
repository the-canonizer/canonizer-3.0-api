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

    public function getMetaTags(Request $request, Validate $validate)
    {
        try {
            $validationErrors = $validate->validate($request, $this->rules->getMetaTagsValidationRules(), $this->validationMessages->getMetaTagsValidationMessages());
            if ($validationErrors) {
                return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
            }

            $page_name = (string)Str::of($request->post('page_name'))->trim();

            $metaTag = (new MetaTag())
                ->where([
                    'page_name' => $page_name,
                ])->first();

            if ($metaTag && $metaTag->is_static == 1) {

                unset($metaTag->is_static);
                unset($metaTag->id);
                $metaTag->keywords = implode('|', (array)$metaTag->keywords);

                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'),  $metaTag, '');
            } else {

                switch ($page_name) {

                    case "TopicDetails":

                        break;

                    default:
                        $validationErrors = $validate->validate($request, $this->rules->getMetaTagsByTopicAndCampValidationRules(), $this->validationMessages->getMetaTagsByTopicAndCampValidationMessages());
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
                            "page_name" => $page_name,
                            "title" => $topic->topic_name,
                            "description" => $topic->note,
                            "submitter_nick_id" => $submitterNick->nick_name,
                            "image_url" => $metaTag->image_url,
                            "keywords" => Str::of($camp->key_words)->replace(',', '|')->replace(' ', ''),
                        ];

                        break;
                }
                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $responseArr, '');
            }
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}
