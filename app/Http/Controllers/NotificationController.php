<?php

namespace App\Http\Controllers;

use stdClass;
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
use Illuminate\Support\Str;

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
     * @OA\Get(
     *     path="/notification-list",
     *     tags={"Notification"},
     *     summary="List notifications",
     *     description="This API retrieves a paginated list of notifications.",
     *     operationId="threadList",
     *     security={{"loginAuthToken":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="error", type="string", nullable=true, example=null),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=10),
     *                         @OA\Property(property="camp_num", type="integer", example=5),
     *                         @OA\Property(property="topic_num", type="integer", example=3),
     *                         @OA\Property(property="message_title", type="string", example="New Notification"),
     *                         @OA\Property(property="message_body", type="string", example="You have a new message"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-02-29T12:34:56Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-02-29T14:00:00Z"),
     *                         @OA\Property(property="notification_type", type="string", example="info"),
     *                         @OA\Property(property="fcm_token", type="string", example="some-fcm-token"),
     *                         @OA\Property(property="is_read", type="integer", example=0)
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="total_rows", type="integer", example=50),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Something went wrong",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Error fetching notifications"),
     *             @OA\Property(property="error", type="string", example="Invalid request"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     )
     * )
     */


    public function notificationList(Request $request, Validate $validate)
    {
        try {
            $perPage = $request->per_page;
            $isSeen = $request->is_seen ?? 0;
            $type = $request->type ?? 'all';
            $userId = $request->user()->id;

            // Base query for notifications
            $notificationQuery = PushNotification::where('user_id', $userId);
            if ($type === 'unread') {
                $notificationQuery->where('is_seen', 0);
            } elseif ($type === 'read') {
                $notificationQuery->where('is_seen', 1);
            }

            // Get notifications based on perPage
            if ($perPage > 0) {
                $notificationList = $notificationQuery->latest()->paginate($perPage);
                $paginatorResponse = Util::getPaginatorResponse($notificationList);
                $notifications = $paginatorResponse->items;
            } else {
                $notifications = $notificationQuery->latest()->get();
            }

            foreach ($notifications as $key => $value) {
                // Set camp_num to 1 if it is null or empty
                $value->camp_num = empty($value->camp_num) ? 1 : $value->camp_num;

                // Skip notification if topic is null or empty
                $topic = Topic::getLiveTopic($value->topic_num ?? '', 'default');

                // Skip this notification if topic is null
                if (empty($topic)) {
                    unset($notifications[$key]); // Remove the notification from the list
                    continue; // Skip to the next notification
                }

                $camp = ($value->camp_num != 0) ? Camp::getLiveCamp(['topicNum' => $value->topic_num, 'campNum' => $value->camp_num, 'asOf' => 'default']) : null;

                // If camp is null or empty, create a default camp object

                if (empty($camp)) {
                    $camp = new stdClass(); // Create an empty object to avoid null property access
                    $camp->camp_num = 1; // Default camp_num
                    $camp->camp_name = 'Agreement'; // Set a default camp_name, can be an empty string too
                    $camp->topic_num = $value->topic_num; // Ensure it has the correct topic_num
                }
                switch ($value->notification_type) {
                    case config('global.notification_type.Topic'):
                        $value->url = Util::topicHistoryLink($topic->topic_num, 1, $topic->topic_name, 'Agreement', 'topic');
                        break;
                    case config('global.notification_type.Camp'):
                        $value->url = Util::topicHistoryLink($camp->topic_num, $camp->camp_num, $topic->topic_name, $camp->camp_name, 'camp');
                        break;
                    case config('global.notification_type.Thread'):
                    case config('global.notification_type.Post'):
                        $value->url = config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . Str::slug($topic->topic_name) . '/' . $camp->camp_num . '-' . Str::slug($camp->camp_name) . '/threads/' . $value->thread_id;
                        break;
                    case config('global.notification_type.Statement'):
                        $value->url = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . Str::slug($topic->topic_name) . '/' . $camp->camp_num . '-' . Str::slug($camp->camp_name);
                        break;
                    case config('global.notification_type.Support'):
                        $value->url = config('global.APP_URL_FRONT_END') . '/topic/' . $topic->topic_num . '-' . Str::slug($topic->topic_name) . '/' . $camp->camp_num . '-' . Str::slug($camp->camp_name);
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
            $readCount = PushNotification::where('user_id', $request->user()->id)->where('is_seen', 1)->count();
            $allCount = PushNotification::where('user_id', $request->user()->id)->count();
            $notifications = (!is_array($notifications)) ? $notifications->toArray() : $notifications;
            $notifications = array_values($notifications);
            if ($perPage > 0) {
                $paginatorResponse->items = $notifications;
                $paginatorResponse->unread_count = $unreadCount;
                $paginatorResponse->read_count = $readCount;
                $paginatorResponse->all_count = $allCount;
                $response = $paginatorResponse;
            } else {
                $response = ['items' => $notifications, 'unread_count' => $unreadCount, 'read_count' => $readCount, 'all_count' => $allCount];
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

    /**
     * @OA\Put(
     *     path= "/notification-is-read/update/{id}",
     *     tags={"Notification"},
     *     summary="Mark notification as read",
     *     description="Updates the read status of a notification",
     *     operationId="updateIsRead",
     *     security={{"loginAuthToken":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Notification ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="error", type="string", nullable=true, example=null),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Something went wrong",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Exception occurred"),
     *             @OA\Property(property="error", type="string", example="Database error"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/notification/read/all",
     *     tags={"Notification"},
     *     summary="Mark all or selected notifications as read",
     *     description="Marks all notifications as read for the authenticated user or updates selected notifications.",
     *     operationId="updateReadAll",
     *     security={{"loginAuthToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_read", type="string", enum={"all", "selected"}, example="all", description="Pass 'all' to mark all notifications as read, or 'selected' to mark specific notifications."),
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1,2,3}, description="Required if 'is_read' is 'selected'. List of notification IDs to mark as read.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="error", type="string", nullable=true, example=null),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or exception",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="error", type="string", example="Invalid data"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     )
     * )
     */

    public function updateReadAll(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->updateReadAllValidationRules(), $this->validationMessages->updateReadAllValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $readAll = $request->is_read ?? 'all'; // all or selected
        $userId =  $request->user()->id;
        try {
            if ($readAll === 'all') {
                PushNotification::where('user_id', $userId)
                    ->where('is_seen', 0)
                    ->update(['is_seen' => 1, 'is_read' => 1, 'seen_time' => time()]);
            } else {
                PushNotification::whereIn('id', $request->ids)
                    ->where('is_seen', 0)
                    ->update([
                        'is_read' => 1,
                        'is_seen' => 1,
                        'seen_time' => time(),
                    ]);
            }

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

    /**
     * @OA\Post(
     *     path="/notification/delete/all",
     *     tags={"Notification"},
     *     summary="Delete all or selected notifications",
     *     description="Deletes all notifications for the authenticated user or deletes selected notifications.",
     *     operationId="deleteAllNotifications",
     *     security={{"loginAuthToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_delete", type="string", enum={"all", "selected"}, example="all", description="Pass 'all' to delete all notifications, or 'selected' to delete specific notifications."),
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1,2,3}, description="Required if 'is_delete' is 'selected'. List of notification IDs to delete.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="error", type="string", nullable=true, example=null),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or exception",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="error", type="string", example="Invalid data"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     )
     * )
     */


    public function deleteAll(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->updateDeleteAllValidationRules(), $this->validationMessages->updateDeleteAllValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $isDelete = $request->is_delete ?? 'all'; // all or selected
        $userId =  $request->user()->id;
        try {
            if ($isDelete === 'all') {
                PushNotification::where('user_id', $userId)->delete();
            } else {
                PushNotification::whereIn('id', $request->ids)->delete();
            }
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

    /**
     * @OA\Post(
     *     path="/update-fcm-token",
     *     tags={"User"},
     *     summary="Update FCM token",
     *     description="Updates the Firebase Cloud Messaging (FCM) token for push notifications. Pass 'disabled' to remove the token.",
     *     operationId="updateFcmToken",
     *     security={{"loginAuthToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="fcm_token", type="string", example="abcd1234xyz", description="FCM token for push notifications. Pass 'disabled' to remove the token.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="error", type="string", nullable=true, example=null),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or exception",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="error", type="string", example="Invalid FCM token"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     )
     * )
     */

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
                $user->fcm_auth_token = null;
                $user->fcm_auth_token_expiry = null;
            } else {
                $user->fcm_token = $request->fcm_token;
                $token = $user->generateOAuthToken('fcm');
                $user->fcm_auth_token = $token['token'];
                $user->fcm_auth_token_expiry = time() + $token['expiry'];
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

    /**
     * @OA\Post(
     *     path="/notify-if-url-not-exist",
     *     tags={"Notification"},
     *     summary="Notify if a URL does not exist",
     *     description="Checks if a topic, statement, nickname, or thread exists. If not, it triggers an administrator notification event.",
     *     operationId="notifyIfUrlNotExist",
     *     security={{"clientAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="url", type="string", example="/topic/123", description="Relative or full URL to check"),
     *             @OA\Property(property="refererURL", type="string", example="https://example.com", description="Referrer URL"),
     *             @OA\Property(property="is_type", type="string", enum={"topic", "statement", "nickname", "thread"}, example="topic", description="Type of entity to check"),
     *             @OA\Property(property="topic_num", type="integer", example=123, description="Topic number (if applicable)"),
     *             @OA\Property(property="camp_num", type="integer", example=1, description="Camp number (if applicable)"),
     *             @OA\Property(property="asof", type="string", example="default", description="Timestamp or 'default' for latest data"),
     *             @OA\Property(property="asOfDate", type="string", example="2025-03-01", description="Specific date for filtering"),
     *             @OA\Property(property="nick_id", type="integer", example=456, description="Nickname ID (if applicable)"),
     *             @OA\Property(property="thread_id", type="integer", example=789, description="Thread ID (if applicable)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="error", type="string", nullable=true, example=null),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_exist", type="boolean", example=true, description="Indicates if the entity exists")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or exception",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="error", type="string", example="Invalid request data"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     )
     * )
     */

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
