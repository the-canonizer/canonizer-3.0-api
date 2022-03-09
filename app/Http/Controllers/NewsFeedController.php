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

    public function get(NewsFeedRequest $request)
    {
        $topicnum = $request->topicnum;
        $parentcampnum = $request->parentcampnum;
        try {
            $news = NewsFeed::where('topic_num', '=', $topicnum)
                ->where('camp_num', '=', $parentcampnum)
                ->orderBy('order_id', 'ASC')->get();
            if ($news) {
                $news = $this->resourceProvider->jsonResponse('NewsFeed', $news);
            }
            return $this->responseProvider->apiJsonResponse(200, config('message.success.success'), $news, '');
        } catch (Exception $e) {
            return $this->responseProvider->apiJsonResponse(400, config('message.error.exception'), $e->getMessage(), '');
        }
    }
}
