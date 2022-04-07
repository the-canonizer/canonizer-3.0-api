<?php

namespace App\Http\Controllers;

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
     *       description="Get topics",
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
        $camp = Camp::liveCampDefaultAsOfFilter($filter);
        try {
            $news = NewsFeed::where('topic_num', '=', $filter['topicNum'])
                ->where('camp_num', '=', $filter['campNum'])
                ->where('end_time', '=', null)
                ->orderBy('order_id', 'ASC')->get();

            if (empty($news) && count($camp) && $camp->parent_camp_num != null) {
                $neCampNum = $camp->parent_camp_num;
                $news = NewsFeed::where('topic_num', '=', $filter['topicNum'])
                    ->where('camp_num', '=', $neCampNum)
                    ->where('end_time', '=', null)
                    ->where('available_for_child', '=', 1)
                    ->orderBy('order_id', 'ASC')->get();
            }
            if ($news) {
                $indexs = NewsFeed::responseIndexes();
                $news = $this->resourceProvider->jsonResponse($indexs, $news);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $news, '',);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }

    /**
     * @OA\Post(path="/edit-camp-newsfeed",
     *   tags={"Camp"},
     *   summary="get camp newsfeed",
     *   description="This is used to get camp newsfeed for editing.",
     *   operationId="getCampNewsFeed",
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
     *       description="Get topics",
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
     *               )
     *           )
     *       )  
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     *  )
     */

    public function editNewsFeed(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedEditValidationRules(), $this->validationMessages->getNewsFeedEditValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $topicNum = $request->topic_num;
        $campNum = $request->camp_num;
        try {
            $news = NewsFeed::where('topic_num', '=', $topicNum)
                ->where('camp_num', '=', $campNum)
                ->where('end_time', '=', null)
                ->orderBy('order_id', 'ASC')->get();
            if ($news) {
                $indexs = NewsFeed::responseIndexes();
                $news = $this->resourceProvider->jsonResponse($indexs, $news);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $news, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
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
     *                  description="display text is required",
     *                  required=true,
     *                  type="array",
     *               ),
     *               @OA\Property(
     *                   property="link",
     *                   description="link is required",
     *                   required=true,
     *                   type="array",
     *               ),
     *               @OA\Property(
     *                   property="available_for_child",
     *                   description="availability for child is required",
     *                   required=true,
     *                   type="array",
     *               ),
     *           )
     *       )
     *   ), 
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */

    public function updateNewsFeed(Request $request, Validate $validate)
    {
        $sizeLimit = $request->display_text ? count($request->display_text) : 0;
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedUpdateValidationRules($sizeLimit), $this->validationMessages->getNewsFeedUpdateValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $campNum = $request->camp_num;
        $topicNum = $request->topic_num;
        $display_text = $request->display_text;
        $link = $request->link;
        $available_for_child = $request->available_for_child;
        $submittime = time();
        NewsFeed::where('camp_num', '=', $campNum)
            ->where('topic_num', '=', $topicNum)
            ->where('end_time', '=', null)
            ->update(['end_time' => $submittime]);

        for ($i = 0; $i < count($display_text); $i++) {
            $news = new NewsFeed();
            $news->topic_num = $topicNum;
            $news->camp_num = $campNum;
            $news->display_text = $display_text[$i];
            $news->link = $link[$i];
            $news->available_for_child = !empty($available_for_child[$i]) ? $available_for_child[$i] : 0;
            $news->submit_time = time();
            $nextOrder = NewsFeed::where('topic_num', '=', $topicNum)->where('camp_num', '=', $campNum)->max('order_id');
            $news->order_id = ++$nextOrder;
            $news->save();
        }

        try {
            $news = NewsFeed::where('topic_num', '=', $topicNum)
                ->where('camp_num', '=', $campNum)
                ->where('end_time', '=', null)
                ->orderBy('order_id', 'ASC')->get();
            if ($news) {
                $indexs = NewsFeed::responseIndexes();
                $news = $this->resourceProvider->jsonResponse($indexs, $news);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $news, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
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
     *                  description="display text is required",
     *                  required=true,
     *                  type="string",
     *               ),
     *               @OA\Property(
     *                   property="link",
     *                   description="link is required",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="available_for_child",
     *                   description="availability for child is required",
     *                   required=true,
     *                   type="boolean",
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
        try {
            $news = new NewsFeed();
            $news->topic_num =  $request->topic_num;
            $news->camp_num = $request->camp_num;
            $news->display_text = $request->display_text;
            $news->link = $request->link;
            $news->available_for_child = $request->available_for_child ?? 0;
            $news->submit_time = time();
            $nextOrder = NewsFeed::where('topic_num', '=', $request->topic_num)->where('camp_num', '=', $request->camp_num)->max('order_id');
            $news->order_id = ++$nextOrder;
            $news->save();
            return $this->resProvider->apiJsonResponse(200, trans('message.success.news_feed_add'), '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }
}
