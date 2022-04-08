<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Thread;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Helpers\ResponseInterface;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Request\ValidationMessages;

class ThreadsController extends Controller
{

    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
        $this->resProvider = $resProvider;
    }

    public function store(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getThreadStoreValidationRules(), $this->validationMessages->getThreadStoreValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $thread_flag = Thread::where('camp_id', $request->camp_num)->where('topic_id', $request->topic_num)->where('title', $request->title)->get();
        if (count($thread_flag) > 0) {
            $status = 400;
            $message = trans('message.thread.title_unique');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
        try {
            $thread = Thread::create([
                'user_id'  => $request->user()->id,
                'title'    => $request->title,
                'body'     => $request->title,
                'camp_id'  => $request->camp_num,
                'topic_id' => $request->topic_num,
            ]);
            if ($thread) {
                $data = $thread;
                $status = 200;
                $message = trans('message.thread.create_success');
            } else {
                $data = null;
                $status = 400;
                $message = trans('message.thread.create_failed');
            }
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }
}
