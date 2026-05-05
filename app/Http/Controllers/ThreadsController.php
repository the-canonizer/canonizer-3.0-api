<?php

namespace App\Http\Controllers;

use stdClass;
use Throwable;
use App\Models\Camp;
use Illuminate\Support\Facades\Event;
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
use App\Http\Request\ValidationMessages;
use phpDocumentor\Reflection\Types\Nullable;
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
     * @OA\POST(
     *   path="/thread/save",
     *   tags={"Thread"},
     *   summary="Create a new thread",
     *   description="This API is used to create a new thread.",
     *   operationId="storeThread",
     *   security={{"loginAuthToken":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Thread creation payload",
     *       @OA\JsonContent(
     *           required={"title", "topic_num", "camp_num", "camp_name", "topic_name", "nick_name"},
     *           @OA\Property(property="title", type="string", description="Thread title"),
     *           @OA\Property(property="topic_num", type="integer", description="Topic number"),
     *           @OA\Property(property="camp_num", type="integer", description="Camp number"),
     *           @OA\Property(property="camp_name", type="string", description="Camp name"),
     *           @OA\Property(property="topic_name", type="string", description="Topic name"),
     *           @OA\Property(property="nick_name", type="integer", description="Nickname ID of the thread creator")
     *       )
     *   ),
     *   @OA\Response(
     *       response=201,
     *       description="Thread created successfully",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=201),
     *           @OA\Property(property="message", type="string", example="Thread created successfully"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="id", type="integer", example=1),
     *               @OA\Property(property="title", type="string", example="My new thread"),
     *               @OA\Property(property="topic_id", type="integer", example=101),
     *               @OA\Property(property="camp_id", type="integer", example=202),
     *               @OA\Property(property="user_id", type="integer", example=5),
     *               @OA\Property(property="created_at", type="string", format="date-time"),
     *               @OA\Property(property="updated_at", type="string", format="date-time")
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad request - validation error or title conflict",
     *       @OA\JsonContent(
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Thread title must be unique")
     *       )
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="Unauthorized - User is not allowed to create a thread",
     *       @OA\JsonContent(
     *           @OA\Property(property="status_code", type="integer", example=403),
     *           @OA\Property(property="message", type="string", example="Invalid nickname provided")
     *       )
     *   ),
     *   @OA\Response(
     *       response=500,
     *       description="Internal server error",
     *       @OA\JsonContent(
     *           @OA\Property(property="status_code", type="integer", example=500),
     *           @OA\Property(property="message", type="string", example="An unexpected error occurred")
     *       )
     *   )
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

            if (!Gate::allows('nickname-check', $request->nick_name)) {
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
                $nickName = '';
                $nicknameModel = Nickname::getNickName($request->nick_name);
                if (!empty($nicknameModel)) {
                    $nickName = $nicknameModel->nick_name;
                }
                $data = $thread;
                $status = 200;
                $message = trans('message.thread.create_success');

                // Return Url after creating thread Successfully
                $return_url =  config('global.APP_URL_FRONT_END') . '/forum/' . $request->topic_num . '-' .  Util::replaceSpecialCharacters($request->topic_name) . '/' . $request->camp_num . '-' . Util::replaceSpecialCharacters($request->camp_name) . '/threads/' . $data->id;
                // $action = config('global.notification_type.Thread');
                // Event::dispatch(new CampForumEvent($request->topic_num, $request->camp_num, $return_url, $request->title, $request->nick_name, $request->topic_name, null, null, $action));
                CampForum::notifySupportersForumThread($request->topic_num, $request->camp_num, $return_url, $request->title, $request->nick_name, $request->topic_name, $thread->id, $nickName, config('global.notify.both'));
                $activitLogData = [
                    'log_type' =>  "threads",
                    'activity' => trans('message.activity_log_message.thread_create', ['nick_name' =>  $nickName]),
                    'url' => $return_url,
                    'model' => $thread,
                    'topic_num' => $request->topic_num,
                    'camp_num' =>   $request->camp_num,
                    'user' => $request->user(),
                    'nick_name' => $nickName,
                    'description' => $request->title,
                    'topic_name' => $request->topic_name,
                    'camp_name' => $request->camp_name,
                    'thread_name' => Util::remove_emoji($request->title)
                ];
                dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
                // GetPushNotificationToSupporter::pushNotificationToSupporter($request->user(), $request->topic_num, $request->camp_num, config('global.notification_type.Thread'), $thread->id, $nickName);
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
     * @OA\Get(
     *   path="/thread/list",
     *   tags={"Thread"},
     *   summary="List threads",
     *   description="Retrieve a list of threads based on query parameters",
     *   operationId="getThreadList", 
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *         name="camp_num",
     *         in="query", 
     *         required=true,
     *         description="Camp number",
     *         @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *         name="topic_num",
     *         in="query", 
     *         required=true,
     *         description="Topic number",
     *         @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=true,
     *         description="Type of thread",
     *         @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Pagination: page number",
     *         @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Pagination: results per page",
     *         @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *         name="like",
     *         in="query",
     *         required=false,
     *         description="Filter by likes",
     *         @OA\Schema(type="boolean")
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful operation",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer"),
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="error", type="string"),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(
     *                   property="items",
     *                   type="array",
     *                   @OA\Items(
     *                       type="object",
     *                       @OA\Property(property="id", type="integer"),
     *                       @OA\Property(property="user_id", type="integer"),
     *                       @OA\Property(property="camp_id", type="integer"),
     *                       @OA\Property(property="topic_id", type="integer"),
     *                       @OA\Property(property="title", type="string"),
     *                       @OA\Property(property="body", type="string"),
     *                       @OA\Property(property="created_at", type="string"),
     *                       @OA\Property(property="updated_at", type="string"),
     *                       @OA\Property(property="nick_name", type="string"),
     *                       @OA\Property(property="post_updated_at", type="string"),
     *                       @OA\Property(property="post_count", type="integer")
     *                   )
     *               ),
     *               @OA\Property(property="current_page", type="integer"),
     *               @OA\Property(property="per_page", type="integer"),
     *               @OA\Property(property="last_page", type="integer"),
     *               @OA\Property(property="total_rows", type="integer"), 
     *               @OA\Property(property="from", type="integer"),
     *               @OA\Property(property="to", type="integer")
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")})
     *   )
     * )
     */

    public function threadList(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getThreadListValidationRules(), $this->validationMessages->getThreadListValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if (!Topic::getLiveTopic($request->topic_num) || !Camp::getLiveCamp(['topicNum' => $request->topic_num, 'campNum' => $request->camp_num])) {
            fwrite(STDOUT, "threadList 404: topic=" . $request->topic_num . " camp=" . $request->camp_num . " topicLive=" . (Topic::getLiveTopic($request->topic_num) ? 'yes' : 'no') . " campLive=" . (Camp::getLiveCamp(['topicNum' => $request->topic_num, 'campNum' => $request->camp_num]) ? 'yes' : 'no') . "\n");
            return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.record_not_found'));
        }
        
        try {
            $threads = null;
            $per_page = !empty($request->per_page) ? $request->per_page : config('global.per_page');
            if ($request->type == config('global.thread_type.allThread')) {
                $threads = $this->getAllThreads($request, $per_page);
                $threads = Util::getPaginatorResponse($threads);
                $this->updateThreadsInfo($threads);
                $status = 200;
                $message = trans('message.success.success');
                
                return $this->resProvider->apiJsonResponse($status, $message, $threads, null);
            }

            if (!$request->user()) {
                $status = 401;
                $message = trans('message.thread.not_authorized');
                return $this->resProvider->apiJsonResponse($status, $message, $threads, null);
            }
            if ($request->type == config('global.thread_type.myThread')) {
                $threads = $this->geMyThreads($request, $per_page);
            }
            if ($request->type == config('global.thread_type.myPrticipate')) {
                $threads = $this->getMyPrticipate($request, $per_page);
            }
            if ($request->type == config('global.thread_type.top10')) {
                $threads = $this->getTop10Threads($request, $per_page);
            }
            $threads = Util::getPaginatorResponse($threads);
            $this->updateThreadsInfo($threads);

            $status = 200;
            $message = trans('message.success.success');
            
            return $this->resProvider->apiJsonResponse($status, $message, $threads, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

    private function getAllThreads($request, $per_page)
    {
        return Thread::leftJoin('post', function ($join) {
            $join->on('thread.id', '=', 'post.c_thread_id')
                ->where('post.is_delete', 0);
        })
            ->leftJoin('nick_name as n1', 'n1.id', '=', 'post.user_id')
            ->leftJoin('nick_name as n2', 'n2.id', '=', 'thread.user_id')
            ->select('thread.*', DB::raw('count(post.c_thread_id) as post_count, max(COALESCE(post.updated_at, thread.created_at)) as post_updated_at'), 'n1.id as nick_name_id', 'n1.nick_name as nick_name', 'n2.id as creation_nick_name_id', 'n2.nick_name as creation_nick_name')
            ->where('camp_id', $request->camp_num)
            ->where('topic_id', $request->topic_num)
            ->when(!empty($request->like), function ($query) use ($request) {
                return $query->where('thread.title', 'LIKE', '%' . $request->like . '%');
            })
            ->groupBy('thread.id')
            ->orderBy('post_updated_at', 'DESC')
            ->paginate($per_page);
    }

    private function geMyThreads($request, $per_page)
    {
        $userNicknames = Nickname::topicNicknameUsed($request->topic_num)->sortBy('nick_name');

        return Thread::leftJoin('post', function ($join) use ($request, $userNicknames) {
            $join->on('thread.id', '=', 'post.c_thread_id')
                ->where('post.is_delete', 0);
        })
            ->leftJoin('nick_name as n1', 'n1.id', '=', 'post.user_id')
            ->leftJoin('nick_name as n2', 'n2.id', '=', 'thread.user_id')
            ->select('thread.*', DB::raw('count(post.c_thread_id) as post_count, max(COALESCE(post.updated_at, thread.created_at)) as post_updated_at'), 'n1.id as nick_name_id', 'n1.nick_name as nick_name', 'n2.id as creation_nick_name_id', 'n2.nick_name as creation_nick_name')
            ->where('camp_id', $request->camp_num)
            ->where('topic_id', $request->topic_num)
            ->where('thread.user_id', $userNicknames[0]->id)
            ->when(!empty($request->like), function ($query) use ($request) {
                return $query->where('thread.title', 'LIKE', '%' . $request->like . '%');
            })
            ->groupBy('thread.id')
            ->orderBy('post_updated_at', 'DESC')
            ->paginate($per_page);
    }

    private function getMyPrticipate($request, $per_page)
    {
        $userNicknames = Nickname::topicNicknameUsed($request->topic_num)->sortBy('nick_name');

        return Thread::leftJoin('post', function ($join) use ($request, $userNicknames) {
            $join->on('thread.id', '=', 'post.c_thread_id')
                ->where('post.is_delete', 0);
        })
            ->leftJoin('nick_name as n1', 'n1.id', '=', 'post.user_id')
            ->leftJoin('nick_name as n2', 'n2.id', '=', 'thread.user_id')
            ->select('thread.*', DB::raw('count(post.c_thread_id) as post_count, max(COALESCE(post.updated_at, thread.created_at)) as post_updated_at'), 'n1.id as nick_name_id', 'n1.nick_name as nick_name', 'n2.id as creation_nick_name_id', 'n2.nick_name as creation_nick_name')
            ->where('camp_id', $request->camp_num)
            ->where('topic_id', $request->topic_num)
            ->where('post.user_id', $userNicknames[0]->id)
            ->when(!empty($request->like), function ($query) use ($request) {
                return $query->where('thread.title', 'LIKE', '%' . $request->like . '%');
            })
            ->groupBy('thread.id')
            ->orderBy('post_updated_at', 'DESC')
            ->paginate($per_page);
    }

    private function getTop10Threads($request, $per_page)
    {
        return Thread::leftJoin('post', function ($join) {
            $join->on('thread.id', '=', 'post.c_thread_id')
                ->where('post.is_delete', 0);
        })
            ->leftJoin('nick_name as n1', 'n1.id', '=', 'post.user_id')
            ->leftJoin('nick_name as n2', 'n2.id', '=', 'thread.user_id')
            ->select('thread.*', DB::raw('count(post.c_thread_id) as post_count'), 'n1.id as nick_name_id', 'n1.nick_name as nick_name', 'n2.id as creation_nick_name_id', 'n2.nick_name as creation_nick_name', 'post.updated_at as post_updated_at')
            ->where('camp_id', $request->camp_num)
            ->where('topic_id', $request->topic_num)
            ->when(!empty($request->like), function ($query) use ($request) {
                return $query->where('thread.title', 'LIKE', '%' . $request->like . '%');
            })
            ->groupBy('thread.id')
            ->orderBy('post_count', 'desc')
            ->paginate($per_page);
    }

    private function updateThreadsInfo($threads)
    {
        if(isset($threads)){
            foreach ($threads->items as $thread) {
                $postCount = Reply::where('c_thread_id', $thread->id)
                    ->where('post.is_delete', 0)
                    ->count();

                $catId = Topic::select('category_id')
                    ->where('topic_num', $thread->topic_id)
                    ->first();
                $thread->category_id = $catId->category_id ?? null;

                $thread->post_count = $postCount;

                if ($postCount > 0) {
                    $latestPost = Reply::where('c_thread_id', $thread->id)
                        ->where('post.is_delete', 0)
                        ->orderBy('post.updated_at', 'DESC')
                        ->first();

                    $thread->post_updated_at = $latestPost->updated_at;
                    $thread->nick_name_id = $latestPost->user_id;

                    $nickName = Nickname::find($latestPost->user_id);
                    if (!empty($nickName)) {
                        $thread->nick_name = $nickName->nick_name;
                    }
                }
            }
        }
    }

    /**
     * @OA\PUT(
     *   path="/thread/update/{id}",
     *   tags={"Thread"},
     *   summary="Update an existing thread",
     *   description="This API is used to update a thread's title.",
     *   operationId="updateThread",
     *   security={{"loginAuthToken":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="Thread ID",
     *       @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Thread update payload",
     *       @OA\JsonContent(
     *           required={"title", "topic_num", "camp_num"},
     *           @OA\Property(property="title", type="string", description="Updated thread title"),
     *           @OA\Property(property="topic_num", type="integer", description="Topic number"),
     *           @OA\Property(property="camp_num", type="integer", description="Camp number"),
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Thread updated successfully",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Thread updated successfully"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="id", type="integer", example=1),
     *               @OA\Property(property="title", type="string", example="Updated thread title"),
     *               @OA\Property(property="topic_id", type="integer", example=101),
     *               @OA\Property(property="camp_id", type="integer", example=202),
     *               @OA\Property(property="created_at", type="string", format="date-time"),
     *               @OA\Property(property="updated_at", type="string", format="date-time")
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad request - validation error or title conflict",
     *       @OA\JsonContent(
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Thread title must be unique")
     *       )
     *   ),
     *   @OA\Response(
     *       response=404,
     *       description="Thread not found",
     *       @OA\JsonContent(
     *           @OA\Property(property="status_code", type="integer", example=404),
     *           @OA\Property(property="message", type="string", example="Thread ID does not exist")
     *       )
     *   ),
     *   @OA\Response(
     *       response=500,
     *       description="Internal server error",
     *       @OA\JsonContent(
     *           @OA\Property(property="status_code", type="integer", example=500),
     *           @OA\Property(property="message", type="string", example="An unexpected error occurred")
     *       )
     *   )
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
            if (!$threads) {
                $threads = null;
                $status = 400;
                $message = trans('message.thread.id_not_exist');
            } else {
                if ($threads->title !== Util::remove_emoji($request->title)) {
                    $thread_flag = Thread::where('camp_id', $request->camp_num)->where('topic_id', $request->topic_num)->where('title', Util::remove_emoji($request->title))->get();
                    if (count($thread_flag) > 0) {
                        $status = 400;
                        $message = trans('message.thread.title_unique');
                        return $this->resProvider->apiJsonResponse($status, $message, null, null);
                    }
                }
                $threads->update($update);
                $url = config('global.APP_URL_FRONT_END') . '/forum/' . $request->topic_num . '-' . Util::replaceSpecialCharacters(Util::remove_emoji($request->title)) . '/'  . $request->camp_num . '-' . Util::replaceSpecialCharacters($request->camp_name) . '/threads/' . $id;
                $nickName = Nickname::getNickName($threads->user_id)->nick_name;
                $activitLogData = [
                    'log_type' =>  "threads",
                    'activity' => trans('message.activity_log_message.thread_update', ['nick_name' =>  $nickName]),
                    'url' => $url,
                    'model' => $threads,
                    'topic_num' => $request->topic_num,
                    'camp_num' =>   $request->camp_num,
                    'user' => $request->user(),
                    'nick_name' => Nickname::getNickName($threads->user_id)->nick_name,
                    'description' => $request->title,
                    'topic_name' => Util::remove_emoji($request->title),
                    'camp_name' => $request->camp_name,
                    'thread_name' => Util::remove_emoji($request->title)
                ];
                dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
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

    /**
     * @OA\Get(
     *   path="/thread/{id}",
     *   tags={"Thread"},
     *   summary="Get thread details by ID",
     *   description="Fetches thread details based on the provided thread ID.",
     *   operationId="getThreadById",
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="Thread ID",
     *       @OA\Schema(type="integer", example=51)
     *   ),
     *   @OA\Parameter(
     *       name="camp_num",
     *       in="query",
     *       required=true,
     *       description="Camp number",
     *       @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Parameter(
     *       name="topic_num",
     *       in="query",
     *       required=true,
     *       description="Topic number",
     *       @OA\Schema(type="integer", example=88)
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful response",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="title", type="string", example="Thread Title"),
     *           @OA\Property(property="camp_id", type="integer", example=3),
     *           @OA\Property(property="topic_id", type="integer", example=2),
     *           @OA\Property(property="namespace_id", type="integer", example=5),
     *           @OA\Property(property="post_count", type="integer", example=10),
     *           @OA\Property(property="post_updated_at", type="string", format="date-time", example="2025-03-03T12:00:00Z"),
     *           @OA\Property(property="nick_name_id", type="integer", example=7),
     *           @OA\Property(property="nick_name", type="string", example="User123"),
     *           @OA\Property(property="creation_nick_name_id", type="integer", example=9),
     *           @OA\Property(property="creation_nick_name", type="string", example="CreatorUser")
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad request",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="error"),
     *           @OA\Property(property="message", type="string", example="Invalid input data.")
     *       )
     *   ),
     *   @OA\Response(
     *       response=404,
     *       description="Thread not found",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="error"),
     *           @OA\Property(property="message", type="string", example="Thread not found or not related to this camp/topic.")
     *       )
     *   ),
     *   @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="error"),
     *           @OA\Property(property="message", type="string", example="Unauthorized access.")
     *       )
     *   )
     * )
     */
    public function getThreadById(Request $request, $id, Validate $validate)
    {
        try {
            request()->merge([ 'thread_id' => (int)$id ]);
            $validationErrors = $validate->validate($request, $this->rules->getThreadByIdValidationRules(), $this->validationMessages->getThreadByIdValidationMessages());
            if ($validationErrors) {
                if ($validationErrors->error->has('thread_id')) {
                    $statusCode = 404;
                    $validationErrors->status_code = 404;
                }
                return (new ErrorResource($validationErrors))->response()->setStatusCode($statusCode ?? 400);
            }
            
            if (!Topic::getLiveTopic($request->topic_num) || !Camp::getLiveCamp(['topicNum' => $request->topic_num, 'campNum' => $request->camp_num])) {
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.record_not_found'));
            }
            
            $threads =  Thread::leftJoin('post', function ($join) {
                $join->on('thread.id', '=', 'post.c_thread_id');
                $join->where('post.is_delete', 0);
            })
                ->leftJoin('nick_name as n1', 'n1.id', '=', 'post.user_id')
                ->leftJoin('nick_name as n2', 'n2.id', '=', 'thread.user_id')
                ->select('thread.*', 'n1.id as nick_name_id', 'n1.nick_name as nick_name', 'n2.id as creation_nick_name_id', 'n2.nick_name as creation_nick_name', 'post.updated_at as post_updated_at')
                ->where('thread.id', $id)
                ->where('topic_id', $request->topic_num)
                ->where('camp_id', $request->camp_num)
                ->first();
            if (!$threads) {
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.thread.thread_not_related'));
            }
            $postCount =  Reply::where('c_thread_id', $threads->id)->where('post.is_delete', 0)->get();
            $catId =  Topic::select('category_id')->where('topic_num', $threads->topic_id)->first();
            $threads->category_id = $catId->category_id ?? null;
            $threads->post_count = $postCount->count();
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
     * @OA\Post(
     *   path="/thread/latest5",
     *   tags={"Thread"},
     *   summary="Get the latest 5 threads",
     *   description="Fetches the latest 5 threads for a given camp and topic.",
     *   operationId="getLatest5Threads",
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *       name="camp_num",
     *       in="query",
     *       required=true,
     *       description="Camp number",
     *       @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Parameter(
     *       name="topic_num",
     *       in="query",
     *       required=true,
     *       description="Topic number",
     *       @OA\Schema(type="integer", example=2)
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful response",
     *       @OA\JsonContent(
     *           type="array",
     *           @OA\Items(
     *               type="object",
     *               @OA\Property(property="id", type="integer", example=1),
     *               @OA\Property(property="title", type="string", example="Thread Title"),
     *               @OA\Property(property="camp_id", type="integer", example=3),
     *               @OA\Property(property="topic_id", type="integer", example=2),
     *               @OA\Property(property="post_count", type="integer", example=10),
     *               @OA\Property(property="post_updated_at", type="string", format="date-time", example="2025-03-03T12:00:00Z"),
     *               @OA\Property(property="nick_name_id", type="integer", example=5),
     *               @OA\Property(property="nick_name", type="string", example="User123"),
     *               @OA\Property(property="creation_nick_name_id", type="integer", example=7),
     *               @OA\Property(property="creation_nick_name", type="string", example="CreatorUser")
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad request",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="error"),
     *           @OA\Property(property="message", type="string", example="Invalid input data.")
     *       )
     *   ),
     *   @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="error"),
     *           @OA\Property(property="message", type="string", example="Unauthorized access.")
     *       )
     *   )
     * )
     */

    public function getLatest5Threads(Request $request)
    {
        return Thread::leftJoin('post', function ($join) {
            $join->on('thread.id', '=', 'post.c_thread_id')
                ->where('post.is_delete', 0);
            })
            ->leftJoin('nick_name as n1', 'n1.id', '=', 'post.user_id')
            ->leftJoin('nick_name as n2', 'n2.id', '=', 'thread.user_id')
            ->select('thread.*', DB::raw('count(post.c_thread_id) as post_count, max(COALESCE(post.updated_at, thread.created_at)) as post_updated_at'), 'n1.id as nick_name_id', 'n1.nick_name as nick_name', 'n2.id as creation_nick_name_id', 'n2.nick_name as creation_nick_name')
            ->where('camp_id', $request->camp_num)
            ->where('topic_id', $request->topic_num)
            ->groupBy('thread.id')
            ->orderBy('post_updated_at', 'DESC')
            ->limit(5)->get();
    }
}
