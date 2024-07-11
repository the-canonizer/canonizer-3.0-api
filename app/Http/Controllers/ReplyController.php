<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use Throwable;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Nickname;
use App\Helpers\CampForum;
use Illuminate\Http\Request;
use App\Events\CampForumEvent;
use App\Http\Request\Validate;
use App\Jobs\ActivityLoggerJob;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Http\Request\ValidationMessages;
use App\Facades\GetPushNotificationToSupporter;

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

    /**
     * @OA\POST(path="/post/save",
     *   tags={"Post"},
     *   summary="save thread",
     *   description="This is use for save post",
     *   operationId="postSave",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="body",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="nick_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="thread_id",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="camp_num",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="topic_num",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="topic_name",
     *                  type="string"
     *              )
     *          )
     *     )
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object",
     *                                          @OA\Property(
     *                                              property="user_id",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="thread_id",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="body",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="created_at",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="updated_at",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="id",
     *                                              type="integer"
     *                                          )
     *                                    )
     *                                 )
     *                            ),
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */

    public function store(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getPostStoreValidationRules(), $this->validationMessages->getPostStoreValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $body_text = trim(html_entity_decode($request->body));
        if (!preg_replace('/\s+/u', '', $body_text)) {
            $status = 400;
            $message = trans('message.post.body_regex');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
        try {

            if (! Gate::allows('nickname-check', $request->nick_name)) {
                return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
            }
            
            $thread = Reply::create([
                'user_id'  => $request->nick_name,
                'body'     => $request->body,
                'c_thread_id'  => $request->thread_id,
            ]);
            $nickName = '';
            $nicknameModel = Nickname::getNickName($request->nick_name);
            if (!empty($nicknameModel)) {
                $nickName = $nicknameModel->nick_name;
            }
            if ($thread) {
                $data = $thread;
                $status = 200;
                $message = trans('message.post.create_success');
                $return_url =  config('global.APP_URL_FRONT_END') .'/forum/' . $request->topic_num . '-' . Util::replaceSpecialCharacters($request->topic_name) . '/' . $request->camp_num.'-'. Util::replaceSpecialCharacters($request->camp_name) . '/threads/' . $request->thread_id;
                CampForum::notifySupportersForumPost($request->topic_num, $request->camp_num, $return_url, $request->body, $request->thread_id, $request->nick_name, $request->topic_name, "", $nickName, config('global.notification_type.Post'), config('global.notify.both'));
                $this->createOrUpdatePostActivityLog($thread, $nickName, $return_url, $request);
            
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

    private function createOrUpdatePostActivityLog($thread, $nickName, $link, $request, $update = false)
    {
        $threadTitle = Thread::where("id", $request->thread_id)->pluck("title")->toArray();
        
        $activitLogData = [
            'log_type' =>  "threads",
            'activity' => trans('message.activity_log_message.post_create', ['nick_name' => $nickName]),
            'url' => $link,
            'model' => $thread,
            'topic_num' => $request->topic_num,
            'camp_num' => $request->camp_num,
            'user' => $request->user(),
            'nick_name' => $nickName,
            'description' =>  $request->body,
            'thread_name' => $threadTitle[0] ?? null
        ];

        if($update) {
            $activitLogData['activity'] = trans('message.activity_log_message.post_update', ['nick_name' => $nickName]);
        }

        dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
    }

    /**
     * @OA\GET(path="/post/list/{id}",
     *   tags={"Post"},
     *   summary="list post",
     *   description="This is use for get post list",
     *   operationId="postList",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="page",
     *         in="url",
     *         required=false,
     *         description="Add page field in query parameters",
     *         @OA\Schema(
     *              type="Query Parameters"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="per_page",
     *         in="url",
     *         required=false,
     *         description="Add per_page field in query parameters",
     *         @OA\Schema(
     *              type="Query Parameters"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="like",
     *         in="url",
     *         required=false,
     *         description="Add like field in query parameters",
     *         @OA\Schema(
     *              type="Query Parameters"
     *         ) 
     *    ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object",
     *                                          @OA\Property(
     *                                              property="items",
     *                                              type="object",
     *                                                  @OA\Property(
     *                                                      property="id",
     *                                                      type="integer"
     *                                                  ),
     *                                                  @OA\Property(
     *                                                      property="user_id",
     *                                                      type="integer"
     *                                                  ),
     *                                                  @OA\Property(
     *                                                      property="camp_id",
     *                                                      type="integer"
     *                                                  ),
     *                                                  @OA\Property(
     *                                                      property="topic_id",
     *                                                      type="integer"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="title",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="body",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="created_at",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="updated_at",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="nick_name",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="post_updated_at",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="post_count",
     *                                                      type="integer"
     *                                                 )
     *                                          ),
     *                                          @OA\Property(
     *                                              property="current_page",
     *                                              type="integer"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="per_page",
     *                                              type="integer"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="last_page",
     *                                              type="integer"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="total_rows",
     *                                              type=""
     *                                          ),
     *                                          @OA\Property(
     *                                              property="from",
     *                                              type="integer"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="to",
     *                                              type="integer"
     *                                          )
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function postList(Request $request, $id)
    {

        try {
            $per_page = !empty($request->per_page) ? $request->per_page : config('global.per_page');

            $result = Reply::leftJoin('nick_name', 'nick_name.id', '=', 'post.user_id')
            ->Join('thread as t', 't.id', '=', 'post.c_thread_id')
            ->select('post.*','nick_name.nick_name','t.topic_id')
            ->where('c_thread_id', $id)->where('is_delete','0')->latest()->paginate($per_page);


            $response = Util::getPaginatorResponse($result);
            if (empty($response)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            foreach ($response->items as $value) {
                $isMyPost = false;
                $allNicname = Nickname::personNicknameArray();
                if (in_array($value->user_id, $allNicname)) {
                    $isMyPost = true;
                }
                $value->is_my_post = $isMyPost;
                $namspaceId =  Topic::select('namespace_id')->where('topic_num',$value->topic_id)->get();
                foreach($namspaceId as $nId){
                    $value->namespace_id = $nId->namespace_id;
                }
            }
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $response, null);
        } catch (Throwable $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $ex->getMessage());
        }
    }

    /**
     * @OA\put(path="/post/update/{id}",
     *   tags={"Post"},
     *   summary="update thread",
     *   description="This is use for update post",
     *   operationId="updateSave",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="body",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="nick_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="thread_id",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="camp_num",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="topic_num",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="topic_name",
     *                  type="string"
     *              )
     *          )
     *     )
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object",
     *                                          @OA\Property(
     *                                              property="user_id",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="thread_id",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="body",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="created_at",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="updated_at",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="id",
     *                                              type="integer"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="is_delete",
     *                                              type="integer"
     *                                          )
     *                                    )
     *                                 )
     *                            ),
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */

    public function update(Request $request, Validate $validate, $id)
    {

        $validationErrors = $validate->validate($request, $this->rules->getPostUpdateValidationRules(), $this->validationMessages->getPostUpdateValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $body_text = trim(html_entity_decode($request->body));
        if (!preg_replace('/\s+/u', '', $body_text)) {
            $status = 400;
            $message = trans('message.post.body_regex');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }

        try {
            $update = ["body" => $request->body];
            $post = Reply::find($id);
            if (!$post) {
                $status = 400;
                $message = trans('message.post.post_not_exist');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            unset($post->thread_id);
            $post->update($update);
            $status = 200;
            $nickName = '';
            $nicknameModel = Nickname::getNickName($post->user_id);
            if (!empty($nicknameModel)) {
                $nickName = $nicknameModel->nick_name;
            }
            $message = trans('message.post.update_success');
            // Return Url after creating post Successfully
            $return_url = config('global.APP_URL_FRONT_END') .'/forum/' . $request->topic_num . '-' . $request->topic_name . '/' . $request->camp_num.'-'.$request->camp_name . '/threads/' . $request->thread_id;
            CampForum::notifySupportersForumPost($request->topic_num, $request->camp_num, $return_url, $request->body, $request->thread_id, $request->nick_name, $request->topic_name, $id, $nickName, 'updatePost', config('global.notify.both'));

            $this->createOrUpdatePostActivityLog($post, $nickName, $return_url, $request, true);

            return $this->resProvider->apiJsonResponse($status, $message, $post, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

    /**
     * @OA\Delete(path="/post/delete/{id}",
     *   tags={"Post"},
     *   summary="delete post",
     *   description="This API is use for delete post",
     *   operationId="postDelete",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Delete a record from this id",
     *         @OA\Schema(
     *              type="integer"
     *         ) 
     *    ),
     *     @OA\Response(
     *         response=200,
     *        description = "Success",
     *        @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                   property="status_code",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="message",
     *                   type="string"
     *               ),
     *              @OA\Property(
     *                   property="error",
     *                   type="string"
     *              ),
     *             @OA\Property(
     *                property="data",
     *                type="string",
     *             ),
     *        ),
     *     ),
     *
     *
     *     @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */

    public function isDelete($id)
    {

        try {
            $update = ["is_delete" => '1'];
            $post = Reply::find($id);
            if (!$post) {
                $status = 400;
                $message = trans('message.post.post_not_exist');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            unset($post->thread_id);
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
