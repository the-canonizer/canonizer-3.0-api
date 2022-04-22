<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use App\Facades\Util;
use App\Models\Reply;
use App\Models\Thread;
use App\Models\Nickname;
use App\Helpers\CampForum;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Request\ValidationMessages;
use phpDocumentor\Reflection\Types\Nullable;

class ReplyController extends Controller
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

        $validationErrors = $validate->validate($request, $this->rules->getPostStoreValidationRules(), $this->validationMessages->getPostStoreValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $body_text = strip_tags(trim(html_entity_decode($request->body)));
        if ( ! preg_replace('/\s+/u', '', $body_text) ) {
            $status = 400;
            $message = trans('message.post.body_regex');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
        try {
            $thread = Reply::create([
                'user_id'  => $request->nick_name,
                'body'     => $body_text,
                'thread_id'  => $request->thread_id,
            ]);
            if ($thread) {
                $data = $thread;
                $status = 200;
                $message = trans('message.post.create_success');

                // Return Url after creating post Successfully
                $return_url = 'forum/' . $request->topic_num . '-' . $request->topic_name . '/' . $request->camp_num . '/threads/' . $request->thread_id;
                CampForum::sendEmailToSupportersForumPost($request->topic_num, $request->camp_num, $return_url,$request->body, $request->thread_id, $request->nick_name, $request->topic_name,"");
            } else {
                $data = null;
                $status = 400;
                $message = trans('message.post.create_failed');
            }
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

    public function postList(Request $request, $id)
    {
        
        try {
            $per_page = !empty($request->per_page) ? $request->per_page : config('global.per_page');

            $result = Reply::where('thread_id', $id)->where('is_delete','0')->paginate($per_page);
            $allNicknames = Util::getPaginatorResponse($result);
            if (empty($allNicknames)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $allNicknames, null);
        } catch (Throwable $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $ex->getMessage());
        }
    }

    public function update(Request $request, Validate $validate, $id)
    {

        $validationErrors = $validate->validate($request, $this->rules->getPostUpdateValidationRules(), $this->validationMessages->getPostUpdateValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $body_text = strip_tags(trim(html_entity_decode($request->body)));
        if ( ! preg_replace('/\s+/u', '', $body_text) ) {
            $status = 400;
            $message = trans('message.post.body_regex');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    
        try {
            $update = ["body" => $body_text];
            $post = Reply::find($id);
            if(!$post){
                $status = 400;
                $message = trans('message.post.post_not_exist');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            $post->update($update);
            $status = 200;
            $message = trans('message.post.update_success');
            // Return Url after creating post Successfully
            $return_url = 'forum/' . $request->topic_num . '-' . $request->topic_name . '/' . $request->camp_num . '/threads/' . $request->thread_id;
            CampForum::sendEmailToSupportersForumPost($request->topic_num, $request->camp_num, $return_url,$request->body, $request->thread_id, $request->nick_name, $request->topic_name,$id);
            return $this->resProvider->apiJsonResponse($status, $message, $post, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

    public function isDelete($id)
    {

        try {
            $update = ["is_delete" => '1'];
            $post = Reply::find($id);
            if(!$post){
                $status = 400;
                $message = trans('message.post.post_not_exist');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            $post->update($update);
            $status = 200;
            $message = trans('message.post.delete_success');

            return $this->resProvider->apiJsonResponse($status, $message, $post, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }
}
