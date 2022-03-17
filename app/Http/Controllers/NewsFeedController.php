<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\NewsFeed;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;
use App\Models\Camp;

class NewsFeedController extends Controller
{
    public function getNewsFeed(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedValidationRules(), $this->validationMessages->getNewsFeedValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['campNum'] = $request->camp_num;
        $camp = Camp::LiveCampdefaultAsOfFilter($filter);
        try {
            $news = NewsFeed::where('topic_num', '=', $filter['topicNum'])
                ->where('camp_num', '=', $filter['campNum'])
                ->where('end_time', '=', null)
                ->orderBy('order_id', 'ASC')->get();
            
            if (!count($news) && count($camp) && $camp->parent_camp_num != null) {
                $neCampNum = $camp->parent_camp_num;
                $news = NewsFeed::where('topic_num', '=', $filter['topicNum'])
                    ->where('camp_num', '=', $neCampNum)
                    ->where('end_time', '=', null)
                    ->where('available_for_child', '=', 1)
                    ->orderBy('order_id', 'ASC')->get();
            }
            if ($news) {
                $news = $this->resourceProvider->jsonResponse('NewsFeed', $news);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $news, '',);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }

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
                $news = $this->resourceProvider->jsonResponse('NewsFeed', $news);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $news, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }

    public function updateNewsFeed(Request $request, Validate $validate)
    {
        $sizeLimit = $request->display_text ? count($request->display_text) : 0;
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedUpdateValidationRules($sizeLimit), $this->validationMessages->getNewsFeedUpdateValidationMessages($request));
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $campNum = $request->camp_num;
        $topicNum = $request->topic_num;
        $display_text = $request->display_text;
        $link = $request->link;
        $available_for_child = $request->available_for_child;
        $submittime = strtotime(date('Y-m-d H:i:s'));
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
            $news->submit_time = strtotime(date('Y-m-d H:i:s'));
            $nextOrder = NewsFeed::where('topic_num', '=', $topicNum)->where('camp_num', '=', $campNum)->max('order_id');
            $news->order_id=++$nextOrder;
            $news->save();
        }
        
        try {
            $news = NewsFeed::where('topic_num', '=', $topicNum)
                ->where('camp_num', '=', $campNum)
                ->where('end_time', '=', null)
                ->orderBy('order_id', 'ASC')->get();
            if ($news) {
                $news = $this->resourceProvider->jsonResponse('NewsFeed', $news);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $news, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }
}
