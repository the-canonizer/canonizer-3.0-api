<?php

namespace App\Http\Controllers;

use stdClass;
use Throwable;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Nickname;
use App\Helpers\CampForum;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Jobs\ActivityLoggerJob;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Request\ValidationMessages;
use phpDocumentor\Reflection\Types\Nullable;
use Illuminate\Support\Facades\Gate;
use App\Facades\GetPushNotificationToSupporter;

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

    /**
     * @OA\POST(path="/thread/save",
     *   tags={"Thread"},
     *   summary="save thread",
     *   description="This is use for save thread",
     *   operationId="threadSave",
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
     *                  property="title",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="nick_name",
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
     *     ),
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
     *                                              property="title",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="body",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="camp_id",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="topic_id",
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

            if (! Gate::allows('nickname-check', $request->nick_name)) {
                return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
            }
            
            $thread = Thread::create([
                'user_id'  => $request->nick_name,
                'title'    => Util::remove_emoji($request->title),
                'body'     => Util::remove_emoji($request->title),
                'camp_id'  => $request->camp_num,
                'topic_id' => $request->topic_num,
            ]);
            if ($thread) {
                $data = $thread;
                $status = 200;
                $message = trans('message.thread.create_success');

                // Return Url after creating thread Successfully
                $return_url =  config('global.APP_URL_FRONT_END') . '/forum/' . $request->topic_num . '-' .  Util::replaceSpecialCharacters($request->topic_name) . '/' . $request->camp_num.'-'. Util::replaceSpecialCharacters($request->camp_name) . '/threads';
                CampForum::sendEmailToSupportersForumThread($request->topic_num, $request->camp_num, $return_url, $request->title, $request->nick_name, $request->topic_name);
                $activitLogData = [
                    'log_type' =>  "threads",
                    'activity' => 'Thread created',
                    'url' => $return_url,
                    'model' => $thread,
                    'topic_num' => $request->topic_num,
                    'camp_num' =>   $request->camp_num,
                    'user' => $request->user(),
                    'nick_name' => Nickname::getNickName($request->nick_name)->nick_name,
                    'description' => $request->title
                ];
                dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('QUEUE_SERVICE_NAME'));
                GetPushNotificationToSupporter::pushNotificationToSupporter($request->user(),$request->topic_num, $request->camp_num, config('global.notification_type.Thread'), $thread->id) ;
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

    /**
     * @OA\GET(path="/thread/list",
     *   tags={"Thread"},
     *   summary="list thread",
     *   description="This is use for get thread list",
     *   operationId="threadList",
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
     *         name="camp_num",
     *         in="url",
     *         required=true,
     *         description="Add camp num field in query parameters",
     *         @OA\Schema(
     *              type="Query Parameters"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="topic_num",
     *         in="url",
     *         required=true,
     *         description="Add topic num field in query parameters",
     *         @OA\Schema(
     *              type="Query Parameters"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="type",
     *         in="url",
     *         required=true,
     *         description="Add type field in query parameters",
     *         @OA\Schema(
     *              type="Query Parameters"
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

    public function threadList(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getThreadListValidationRules(), $this->validationMessages->getThreadListValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $threads = null;
            $per_page = !empty($request->per_page) ? $request->per_page : config('global.per_page');
            if ($request->type == config('global.thread_type.allThread')) {
                $query = Thread::leftJoin('post', function($join) {
                        $join->on('thread.id', '=', 'post.c_thread_id');
                        $join->where('post.is_delete',0);
                    })
                    ->leftJoin('nick_name as n1', 'n1.id', '=', 'post.user_id')
                    ->leftJoin('nick_name as n2', 'n2.id', '=', 'thread.user_id')
                    ->select('thread.*', DB::raw('count(post.c_thread_id) as post_count'),'n1.id as nick_name_id', 'n1.nick_name as nick_name','n2.id as creation_nick_name_id','n2.nick_name as creation_nick_name','post.updated_at as post_updated_at')
                    ->where('camp_id', $request->camp_num)->where('topic_id', $request->topic_num);
                if (!empty($request->like)) {
                    $query->where('thread.title', 'LIKE', '%' . $request->like . '%');
                }
                $threads = $query->groupBy('thread.id')->latest()->paginate($per_page);
                $threads = Util::getPaginatorResponse($threads);
                foreach($threads->items as $value){
                    $postCount =  Reply::where('c_thread_id',$value->id)->where('post.is_delete',0)->get();
                    $namspaceId =  Topic::select('namespace_id')->where('topic_num',$value->topic_id)->get();
                    foreach($namspaceId as $nId){
                        $value->namespace_id = $nId->namespace_id;
                    }
                    $value->post_count = $postCount->count();
                }
                $status = 200;
                $message = trans('message.success.success');
                return $this->resProvider->apiJsonResponse($status, $message, $threads, null);
            }
            if (!$request->user()) {
                $status = 401;
                $message = trans('message.thread.not_authorized');
                return $this->resProvider->apiJsonResponse($status, $message, $threads, null);
            }
            $userNicknames = Nickname::topicNicknameUsed($request->topic_num)->sortBy('nick_name');
            $query = Thread::leftJoin('post', function($join) {
                    $join->on('thread.id', '=', 'post.c_thread_id');
                    $join->where('post.is_delete',0);
                })
                ->leftJoin('nick_name as n1', 'n1.id', '=', 'post.user_id')
                ->leftJoin('nick_name as n2', 'n2.id', '=', 'thread.user_id')
                ->select('thread.*', DB::raw('count(post.c_thread_id) as post_count'),'n1.id as nick_name_id', 'n1.nick_name as nick_name','n2.id as creation_nick_name_id','n2.nick_name as creation_nick_name' ,'post.updated_at as post_updated_at')
                ->where('camp_id', $request->camp_num)->where('topic_id', $request->topic_num);
            if (!empty($request->like)) {
                $query->where('thread.title', 'LIKE', '%' . $request->like . '%');
            }
           
            if ($request->type == config('global.thread_type.myThread')) {
                if (count($userNicknames) > 0) {
                    $query->where('thread.user_id', $userNicknames[0]->id)->groupBy('thread.id');
                }
            }
            if ($request->type == config('global.thread_type.myPrticipate')) {
                if (count($userNicknames) > 0) {
                    $query->where('post.user_id', $userNicknames[0]->id)->groupBy('thread.id');
                }
            }
            $threads = $query->latest()->paginate($per_page);
            if ($request->type == config('global.thread_type.top10')) {
                $query = Thread::leftJoin('post', function($join) {
                        $join->on('thread.id', '=', 'post.c_thread_id');
                        $join->where('post.is_delete',0);
                    })
                    ->leftJoin('nick_name as n1', 'n1.id', '=', 'post.user_id')
                    ->leftJoin('nick_name as n2', 'n2.id', '=', 'thread.user_id')
                    ->select('thread.*', DB::raw('count(post.c_thread_id) as post_count'),'n1.id as nick_name_id', 'n1.nick_name as nick_name','n2.id as creation_nick_name_id','n2.nick_name as creation_nick_name','post.updated_at as post_updated_at')
                    ->where('camp_id', $request->camp_num)->where('topic_id', $request->topic_num);
                if (!empty($request->like)) {
                    $query->where('thread.title', 'LIKE', '%' . $request->like . '%');
                }
                $threads = $query->groupBy('thread.id')->orderBy('post_count', 'desc')->latest()->paginate($per_page);
            }
            $threads = Util::getPaginatorResponse($threads);
            foreach($threads->items as $value){
                $postCount =  Reply::where('c_thread_id',$value->id)->where('post.is_delete',0)->get();
                $namspaceId =  Topic::select('namespace_id')->where('topic_num',$value->topic_id)->get();
                foreach($namspaceId as $nId){
                    $value->namespace_id = $nId->namespace_id;
                }
                $value->post_count = $postCount->count();
            }
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $threads, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

     /**
     * @OA\PUT(path="/thread/update",
     *   tags={"Thread"},
     *   summary="update thread",
     *   description="This is use for update thread",
     *   operationId="threadUpdate",
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
     *         in="url",
     *         required=true,
     *         description="send thread id in url",
     *         @OA\Schema(
     *              type="Value Parameters"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="title",
     *                  type="string"
     *              )
     *          )
     *     ),
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
     *                                              property="title",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="body",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="camp_id",
     *                                              type="string"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="topic_id",
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

    public function update(Request $request, Validate $validate, $id)
    {
        $validationErrors = $validate->validate($request, $this->rules->getThreadUpdateValidationRules(), $this->validationMessages->getThreadUpdateValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $update = ["title" =>  Util::remove_emoji($request->title)];
            $threads = Thread::find($id);
            if(!$threads){
                $threads = null;
                $status = 400;
                $message = trans('message.thread.id_not_exist');
            }else{
                if($threads->title !=Util::remove_emoji($request->title)){
                    $thread_flag = Thread::where('camp_id', $request->camp_num)->where('topic_id', $request->topic_num)->where('title', Util::remove_emoji($request->title))->get();
                    if (count($thread_flag) > 0) {
                        $status = 400;
                        $message = trans('message.thread.title_unique');
                        return $this->resProvider->apiJsonResponse($status, $message, null, null);
                    }
                }
                $threads->update($update);
                $topic_name = CampForum::getTopicName($threads->topic_id);
                $camp_name = CampForum::getCampName($threads->topic_id,$threads->camp_id);
                $url = 'forum/' . $request->topic_num . '-' .   urlencode(Util::remove_emoji($request->title)) . '/'  . $request->camp_num . '-' . urlencode($request->camp_name) . '/threads';
                $activitLogData = [
                    'log_type' =>  "threads",
                    'activity' => 'Thread updated',
                    'url' => $url,
                    'model' => $threads,
                    'topic_num' => $request->topic_num,
                    'camp_num' =>   $request->camp_num,
                    'user' => $request->user(),
                    'nick_name' => Nickname::getNickName($threads->user_id)->nick_name,
                    'description' => $request->title
                ];
                dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('QUEUE_SERVICE_NAME'));
                $status = 200;
                $message = trans('message.thread.update_success');
            }
            return $this->resProvider->apiJsonResponse($status, $message, $threads, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }
}
