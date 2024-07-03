<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Facades\Util;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Nickname;
use App\Models\Statement;
use App\Helpers\CampForum;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Models\PushNotification;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Events\NotifyAdministratorEvent;
use App\Http\Request\ValidationMessages;

class NotificationController extends Controller
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
     *                                                      property="camp_num",
     *                                                      type="integer"
     *                                                  ),
     *                                                  @OA\Property(
     *                                                      property="topic_num",
     *                                                      type="integer"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="message_title",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="message_body",
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
     *                                                      property="notification_type",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="fcm_token",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="is_read",
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

    public function notificationList(Request $request, Validate $validate)
    {
        try {
            $perPage = $request->per_page;
            $isSeen = $request->is_seen ?? 0;

            if ($perPage > 0) {
                $notificationList = PushNotification::where('user_id', $request->user()->id)->latest()->paginate($perPage);
                $paginatorResponse = Util::getPaginatorResponse($notificationList);
                $notifications = $paginatorResponse->items;
            } else {
                $notifications = PushNotification::where('user_id', $request->user()->id)->latest()->get();
            }

            foreach ($notifications as $value) {
                $topic = Topic::getLiveTopic($value->topic_num ?? '', 'default');
                $camp = ($value->camp_num ?? '' != 0) ? Camp::getLiveCamp(['topicNum' => $value->topic_num, 'campNum' => $value->camp_num, 'asOf' => 'default']) : null;

                switch ($value->notification_type) {
                    case config('global.notification_type.Topic'):
                        $value->url = Util::topicHistoryLink($topic->topic_num, 1, $topic->topic_name, 'Aggreement', 'topic');
                        break;
                    case config('global.notification_type.Camp'):
                        $value->url = Util::topicHistoryLink($camp->topic_num, $camp->camp_num, $topic->topic_name, $camp->camp_name, 'camp');
                        break;
                    case config('global.notification_type.Thread'):
                    case config('global.notification_type.Post'):
                        $value->url = config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name . '/threads/' . $value->thread_id;
                        break;
                    case config('global.notification_type.Statement'):
                        $value->url = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                        break;
                    case config('global.notification_type.Support'):
                        $value->url = config('global.APP_URL_FRONT_END') . '/topic/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                        break;
                    case config('global.notification_type.objectCamp'):
                        $value->url = config('global.APP_URL_FRONT_END') . '/camp/history/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name);
                        break;
                    case config('global.notification_type.objectTopic'):
                        $value->url = config('global.APP_URL_FRONT_END') . '/topic/history/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name);
                        break;
                    case config('global.notification_type.objectStatement'):
                        $value->url = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name);
                        break;
                    default:
                        $value->url = Camp::campLink($camp->topic_num ?? '', $camp->camp_num ?? '', $topic->topic_name ?? '', $camp->camp_name ?? '');
                }
            }

            if ($isSeen) {
                PushNotification::where('user_id', $request->user()->id)
                    ->where('is_seen', 0)
                    ->update(['is_seen' => 1, 'seen_time' => time()]);
            }

            $unreadCount = PushNotification::where('user_id', $request->user()->id)->where('is_seen', 0)->count();

            if ($perPage > 0) {
                $paginatorResponse->items = $notifications;
                $paginatorResponse->unread_count = $unreadCount;
                $response = $paginatorResponse;
            } else {
                $response = ['items' => $notifications, 'unread_count' => $unreadCount];
            }

            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $response, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

    public function updateIsRead(Request $request, $id)
    {
        try {
            $PushNotification = PushNotification::find($id);
            $PushNotification->is_read = 1;
            $PushNotification->is_seen = 1;
            $PushNotification->seen_time = time();
            $PushNotification->save();
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

    public function updateReadAll(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->updateReadAllValidationRules(), $this->validationMessages->updateReadAllValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            DB::beginTransaction();
            PushNotification::whereIn('id', $request->ids)->update([
                'is_read' => 1,
                'is_seen' => 1,
                'seen_time' => time(),
            ]);
            DB::commit();
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Throwable $e) {
            DB::rollBack();
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

    public function deleteAll(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->updateDeleteAllValidationRules(), $this->validationMessages->updateDeleteAllValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            DB::beginTransaction();
            PushNotification::whereIn('id', $request->ids)->delete();
            DB::commit();
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Throwable $e) {
            DB::rollBack();
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

    public function updateFcmToken(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getUpdateFcmTokenValidationRules(), $this->validationMessages->getFcmTokenValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $user = User::find($request->user()->id);
            if ($request->fcm_token == 'disabled') {
                $user->fcm_token = null;
            } else {
                $user->fcm_token = $request->fcm_token;
            }
            $user->save();
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }

    public function notifyIfUrlNotExist(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate(
            $request,
            $this->rules->notifyIfTopicNotExistValidationRules(),
            $this->validationMessages->notifyIfTopicNotExistValidationMessages()
        );
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $data = ['is_exist' => true];
            $isExternal = strpos($request->url, 'http') === 0;
            $baseUrl = $isExternal ? '' : env('APP_URL_FRONT_END');
            $url = $baseUrl . $request->url;
            $refererURL = $request->refererURL;

            switch ($request->is_type) {
                case 'topic':
                    $topic = Topic::getLiveTopic($request->topic_num);
                    $camp = Camp::getLiveCamp([
                        'topicNum' => $request->topic_num,
                        'asOf' => $request->asof ?? '',
                        'campNum' => $request->camp_num,
                    ]);
                    if (empty($topic) || empty($camp)) {
                        $data = ['is_exist' => false];
                        Event::dispatch(new NotifyAdministratorEvent($url, $refererURL));
                    }
                    break;
                case 'statement':
                    $campStatement = Statement::getLiveStatement([
                        'topicNum' => $request->topic_num,
                        'asOf' => $request->asof ?? '',
                        'asOfDate' => $request->asOfDate ?? '',
                        'campNum' => $request->camp_num,
                    ]);
                    if (empty($campStatement)) {
                        $data = ['is_exist' => false];
                        Event::dispatch(new NotifyAdministratorEvent($url, $refererURL));
                    }
                    break;
                case 'nickname':
                    $nickname = Nickname::getNickName($request->nick_id);
                    if (empty($nickname)) {
                        $data = ['is_exist' => false];
                        Event::dispatch(new NotifyAdministratorEvent($url, $refererURL));
                    }
                    break;
                case 'thread':
                    $thread = Thread::find($request->thread_id);
                    if (empty($thread)) {
                        $data = ['is_exist' => false];
                        Event::dispatch(new NotifyAdministratorEvent($url, $refererURL));
                    }
                    break;
                default:
                    $data = ['is_exist' => false];
                    Event::dispatch(new NotifyAdministratorEvent($url, $refererURL));
                    break;
            }

            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Throwable $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
        }
    }
}
