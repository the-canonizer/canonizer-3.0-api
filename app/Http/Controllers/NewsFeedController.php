<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\NewsFeed;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Request\ValidationMessages;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;

class NewsFeedController extends Controller
{
    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider)
    {
        $this->resourceProvider = $resProvider;
        $this->responseProvider = $respProvider;
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
    }

    public function getNewsFeed(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getNewsFeedValidateionRules(), $this->validationMessages->getNewsFeedValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
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
