<?php

namespace App\Http\Controllers;

use App\Models\NewsFeed;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\NewsFeedRequest;

class NewsFeedController extends Controller
{
    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider)
    {
        $this->resourceProvider = $resProvider;
        $this->responseProvider = $respProvider;
    }

    public function getNewsFeed(NewsFeedRequest $request)
    {
        $topicNum = $request->topic_num;
        $campNum = $request->camp_num;
        try {
            $news = NewsFeed::where('topic_num', '=', $topicNum)
                ->where('camp_num', '=',$campNum)
                ->orderBy('order_id', 'ASC')->get();
            if ($news) {
                $news = $this->resourceProvider->jsonResponse('NewsFeed', $news);
            }
            return $this->responseProvider->apiJsonResponse(200, trans('message.success.success'), $news, '');
        } catch (Exception $e) {
            return $this->responseProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }
}
