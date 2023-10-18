<?php

namespace App\Http\Controllers;

use App\Helpers\TopicSupport;
use Exception;
use App\Models\NewsFeed;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;
use App\Models\Camp;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;
use App\Models\Nickname;
use App\Jobs\ActivityLoggerJob;
use App\Helpers\Util;
use App\Helpers\CampForum;

class NewsFeedController extends Controller
{

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\Post(path="/get-camp-newsfeed",
     *   tags={"Camp"},
     *   summary="get camp newsfeed",
     *   description="This is used to get camp newsfeed.",
     *   operationId="getCampNewsFeed",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get Newsfeed",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *              @OA\Property(
     *                  property="topic_num",
     *                  description="Topic number is required",
     *                  required=true,
     *                  type="integer",
     *              ),
     *              @OA\Property(
     *                  property="camp_num",
     *                  description="Camp number is required",
     *                  required=true,
     *                  type="integer",
     *              )
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */

    public function getNewsFeed(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedValidationRules(), $this->validationMessages->getNewsFeedValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['campNum'] = $request->camp_num;
        $manageFlag = true;
        $parentCampName = $parentCampUrl = Null;
        $camp = Camp::liveCampDefaultAsOfFilter($filter);
        try {
            $news = NewsFeed::where('topic_num', '=', $filter['topicNum'])
                ->where('camp_num', '=', $filter['campNum'])
                ->where('end_time', '=', null)
                ->orderBy('order_id', 'ASC')->get();

            if ($news->isEmpty() && !empty($camp) && $camp->parent_camp_num != null) {
                $parentCampNum = $camp->parent_camp_num;
                $news = NewsFeed::where('topic_num', '=', $filter['topicNum'])
                    ->where('camp_num', '=', $parentCampNum)
                    ->where('end_time', '=', null)
                    ->where('available_for_child', '=', 1)
                    ->orderBy('order_id', 'ASC')->get();
                $manageFlag = false;
                $filter['campNum'] = $parentCampNum;
                $topic = Camp::getAgreementTopic($filter);
                $camp  = TopicSupport::getLiveCamp($filter);
                $parentCampName = CampForum::getCampName($filter['topicNum'], $parentCampNum);
                $parentCampUrl = Util::getTopicCampUrl($filter['topicNum'], $parentCampNum, $topic, $camp);
            }
            foreach ($news as $newsfeed) {
                $newsfeed->parent_camp_url = $parentCampUrl;
                $newsfeed->parent_camp_name = $parentCampName;
                $newsfeed->submitter_nick_name = $newsfeed->nickName ? $newsfeed->nickName->nick_name : "";
                if ($request->user()) {
                    ($newsfeed->author_id == $request->user()->id || $request->user()->type == "admin") ?
                        ($newsfeed->owner_flag = true and $newsfeed->manage_flag = $manageFlag) : ($newsfeed->owner_flag = false and $newsfeed->manage_flag = false);
                }
            }
            $indexes = ['id', 'display_text', 'link', 'available_for_child', 'submitter_nick_name', 'submit_time', 'owner_flag', 'manage_flag', 'parent_camp_name', 'parent_camp_url'];
            $news = $this->resourceProvider->jsonResponse($indexes, $news);
            
            if (count($news) < 1)
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.camp_news_feed_not_found'));
            
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $news, '',);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/update-camp-newsfeed",
     *   tags={"Camp"},
     *   summary="Update camp newsfeed",
     *   description="This is used to update camp newsfeed.",
     *   operationId="updateCampNewsFeed",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Update Camp Newsfeed",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="newsfeed_id",
     *                   description="ID of the newsfeed",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="submitter_nick_id",
     *                   description="Submitter nick name id",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                  property="display_text",
     *                  description="Display text is required",
     *                  required=true,
     *                  type="array",
     *               ),
     *               @OA\Property(
     *                   property="link",
     *                   description="Link is required",
     *                   required=true,
     *                   type="array",
     *               ),
     *               @OA\Property(
     *                   property="available_for_child",
     *                   description="Availability for child is required",
     *                   required=true,
     *                   type="array",
     *               ),
     *           )
     *       )
     *   ), 
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

    public function updateNewsFeed(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedUpdateValidationRules(), $this->validationMessages->getNewsFeedUpdateValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $newsFeedId = $request->newsfeed_id;
        $display_text = $request->display_text;
        $submitterNickId = $request->submitter_nick_id;
        $link = $request->link;
        $userId = $request->user()->id;
        $userType = $request->user()->type;
        $availableForChild = $request->available_for_child;
        try {
            $newsFeed = NewsFeed::findOrFail($newsFeedId);
            if ($newsFeed->author_id != $userId && $userType != "admin") {
                return $this->resProvider->apiJsonResponse(401, trans('message.general.permission_denied'), '', '');
            }
            $topicNum = $newsFeed->topic_num;
            $campNum = $newsFeed->camp_num;
            $newsFeed->end_time = time();
            $newsFeed->save();
            $news = new NewsFeed();
            $news->topic_num =  $topicNum;
            $news->camp_num =  $campNum;
            $news->author_id = $userId;
            $news->display_text = $display_text;
            $news->link = $link;
            $news->submitter_nick_id = $submitterNickId;
            $news->available_for_child = $availableForChild;
            $news->submit_time = time();
            $nextOrder = NewsFeed::where('topic_num', '=', $topicNum)->where('camp_num', '=', $campNum)->max('order_id');
            $news->order_id = ++$nextOrder;
            $news->save();
            $topicFilter = ['topicNum' => $news->topic_num];
            $campFilter = ['topicNum' => $news->topic_num, 'campNum' => $news->camp_num];
            $topic = Camp::getAgreementTopic($topicFilter);
            $camp  = TopicSupport::getLiveCamp($campFilter);
            $url = Util::getTopicCampUrl($news->topic_num, $news->camp_num, $topic, $camp);
            $nickName = Nickname::getNickName($submitterNickId)->nick_name;
            $activitLogData = [
                'log_type' =>  "topic/camps",
                'activity' => trans('message.activity_log_message.news_update', ['nick_name' => $nickName]),
                'url' => $url,
                'model' => $news,
                'topic_num' => $topicNum,
                'camp_num' =>  $campNum,
                'user' => $request->user(),
                'nick_name' => $nickName,
                'description' =>  $display_text
            ];
            dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
            $temp[] = $news;
            $indexes = NewsFeed::apiResponseIndexes();
            $news = $this->resourceProvider->jsonResponse($indexes, $temp);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $news, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/store-camp-newsfeed",
     *   tags={"Camp"},
     *   summary="Store camp newsfeed",
     *   description="This is used to store camp newsfeed.",
     *   operationId="storeCampNewsFeed",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Store Camp Newsfeed",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="Topic number is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                  property="display_text",
     *                  description="Display text is required",
     *                  required=true,
     *                  type="string",
     *               ),
     *               @OA\Property(
     *                   property="link",
     *                   description="Link is required",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="available_for_child",
     *                   description="Availability for child is required",
     *                   required=true,
     *                   type="boolean",
     *               ),
     *               @OA\Property(
     *                   property="submitter_nick_id",
     *                   description="Nick name id of the submitter",
     *                   required=true,
     *                   type="integer",
     *               ),
     *           )
     *       )
     *   ), 
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

    public function storeNewsFeed(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedStoreValidationRules(), $this->validationMessages->getNewsFeedStoreValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $nickNameIds = Nickname::getNicknamesIdsByUserId($request->user()->id);
        if (!in_array($request->submitter_nick_id, $nickNameIds)) {
            return $this->resProvider->apiJsonResponse(400, trans('message.general.nickname_association_absence'), '', '');
        }
        try {
            $news = new NewsFeed();
            $news->author_id = $request->user()->id;
            $news->submitter_nick_id = $request->submitter_nick_id;
            $news->topic_num =  $request->topic_num;
            $news->camp_num = $request->camp_num;
            $news->display_text = $request->display_text;
            $news->link = $request->link;
            $news->available_for_child = $request->available_for_child ?? 0;
            $news->submit_time = time();
            $nextOrder = NewsFeed::where('topic_num', '=', $request->topic_num)->where('camp_num', '=', $request->camp_num)->max('order_id');
            $news->order_id = ++$nextOrder;
            $news->save();
            $topicFilter = ['topicNum' => $request->topic_num];
            $campFilter = ['topicNum' => $request->topic_num, 'campNum' => $request->camp_num];
            $topic = Camp::getAgreementTopic($topicFilter);
            $camp  = TopicSupport::getLiveCamp($campFilter);
            $url = Util::getTopicCampUrl($request->topic_num, $request->camp_num, $topic, $camp);
            $nickName = Nickname::getNickName($request->submitter_nick_id)->nick_name;
            $activitLogData = [
                'log_type' =>  "topic/camps",
                'activity' => trans('message.activity_log_message.news_create', ['nick_name' => $nickName]),
                'url' => $url,
                'model' => $news,
                'topic_num' => $request->topic_num,
                'camp_num' => $request->camp_num,
                'user' => $request->user(),
                'nick_name' => $nickName,
                'description' =>  $request->display_text
            ];
            dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
            return $this->resProvider->apiJsonResponse(200, trans('message.success.news_feed_add'), '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/delete-camp-newsfeed",
     *   tags={"Camp"},
     *   summary="delete camp newsfeed",
     *   description="This is used to delete camp newsfeed.",
     *   operationId="deleteCampNewsFeed",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="delete camp newsfeed",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="id",
     *                   description="Camp newsfeed id is required",
     *                   required=true,
     *                   type="integer",
     *               )
     *           )
     *       )  
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message"),
     *   @OA\Response(response=401, description="Unauthenticated")
     *  )
     */

    public function deleteNewsFeed(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedDeleteValidationRules(), $this->validationMessages->getNewsFeedDeleteValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $newsId = $request->newsfeed_id;
        $userId = $request->user()->id;
        $newsFeed = NewsFeed::findOrFail($newsId);
        try {
            if ($newsFeed->author_id == $userId || $request->user()->type == "admin") {
                $newsFeed->delete();
                $topicFilter = ['topicNum' => $newsFeed->topic_num];
                $campFilter = ['topicNum' => $newsFeed->topic_num, 'campNum' => $newsFeed->camp_num];
                $topic = Camp::getAgreementTopic($topicFilter);
                $camp  = TopicSupport::getLiveCamp($campFilter);
                $url = Util::getTopicCampUrl($newsFeed->topic_num, $newsFeed->camp_num, $topic, $camp);
                $nickName = Nickname::getNickName($newsFeed->submitter_nick_id)->nick_name ?? "";
                $activitLogData = [
                    'log_type' =>  "topic/camps",
                    'activity' => trans('message.activity_log_message.news_delete', ['nick_name' => $nickName]),
                    'url' => $url,
                    'model' => $newsFeed,
                    'topic_num' => $newsFeed->topic_num,
                    'camp_num' =>  $newsFeed->camp_num,
                    'user' => $request->user(),
                    'nick_name' =>  $nickName,
                    'description' =>  $newsFeed->display_text
                ];
                dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), '', '');
            } else {
                return $this->resProvider->apiJsonResponse(401, trans('message.general.permission_denied'), '', '');
            }
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/edit-camp-newsfeed",
     *   tags={"Camp"},
     *   summary="edit camp newsfeed",
     *   description="This is used to get  a record for updating camp newsfeed.",
     *   operationId="editCampNewsFeed",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="edit camp newsfeed",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="id",
     *                   description="Camp newsfeed id is required",
     *                   required=true,
     *                   type="integer",
     *               )
     *           )
     *       )  
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message"),
     *   @OA\Response(response=401, description="Unauthenticated")
     *  )
     */
    public function editNewsFeed(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedEditValidationRules(), $this->validationMessages->getNewsFeedEditValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $newsId = $request->newsfeed_id;
        $userId = $request->user()->id;
        $temp = [];
        try {
            $newsFeed = NewsFeed::findOrFail($newsId);
            if ($newsFeed->author_id == $userId || $request->user()->type == "admin") {
                $indexes = NewsFeed::apiResponseIndexes();
                $temp[] = $newsFeed;
                $newsFeed = $this->resourceProvider->jsonResponse($indexes, $temp);
            } else {
                return $this->resProvider->apiJsonResponse(401, trans('message.general.permission_denied'), '', '');
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $newsFeed, '',);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}
