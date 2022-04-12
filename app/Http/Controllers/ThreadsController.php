<?php

namespace App\Http\Controllers;

use App\Helpers\CampForum;
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
            $thread = Thread::create([
                'user_id'  => $request->nick_name,
                'title'    => $request->title,
                'body'     => $request->title,
                'camp_id'  => $request->camp_num,
                'topic_id' => $request->topic_num,
            ]);
            if ($thread) {
                $data = $thread;
                $status = 200;
                $message = trans('message.thread.create_success');

                // Return Url after creating thread Successfully
                $return_url = 'forum/' . $request->topic_num . '-' . $request->topic_name . '/' . $request->camp_num . '/threads';
                CampForum::sendEmailToSupportersForumThread($request->topic_num, $request->camp_num, $return_url, $request->title, $request->nick_name, $request->topic_name);
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
