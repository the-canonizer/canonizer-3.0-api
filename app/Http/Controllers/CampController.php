<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Nickname;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Helpers\ResponseInterface;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Http\Request\ValidationMessages;
use App\Events\ThankToSubmitterMailEvent;
use App\Helpers\ResourceInterface;

class CampController extends Controller
{

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }


    /**
     * @OA\POST(path="/camp/save",
     *   tags={"Camp"},
     *   summary="save camp",
     *   description="This API is use for save camp",
     *   operationId="campSave",
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
     *                  property="camp_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="parent_camp_num",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="topic_num",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="nick_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="note",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="key_words",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="camp_about_url",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="camp_about_nick_id",
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
     *                                           @OA\Property(
     *                                              property="camp_num",
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
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function store(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getCampStoreValidationRules(), $this->validationMessages->getCampStoreValidationMessages());

        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {

            $current_time = time();

            ## check if mind_expert topic and camp abt nick name id is null then assign nick name as about nickname ##
            if ($request->topic_num == config('global.mind_expert_topic_num') && !isset($request->camp_about_nick_id)) {
                $request->camp_about_nick_id = $request->nick_name ?? "";
            } else {
                $request->camp_about_nick_id = $request->camp_about_nick_id ?? "";
            }
           
            $nextCampNum =  Camp::where('topic_num', $request->topic_num)
                ->latest('submit_time')->first();
            $nextCampNum->camp_num++;
            $input = [
                "camp_name" => $request->camp_name,
                "camp_num" => $nextCampNum->camp_num,
                "parent_camp_num" => $request->parent_camp_num,
                "topic_num" => $request->topic_num,
                "submit_time" => strtotime(date('Y-m-d H:i:s')),
                "submitter_nick_id" => $request->nick_name,
                "go_live_time" =>  $current_time,
                "language" => 'English',
                "note" => $request->note ?? "",
                "key_words" => $request->key_words ?? "",
                "camp_about_url" => $request->camp_about_url ?? "",
                "title" => $request->title ?? "",
                "camp_about_nick_id" =>  $request->camp_about_nick_id,
                "grace_period" => 1
            ];

            $camp = Camp::create($input);
           
            if ($camp) {

                $topic = Topic::getLiveTopic($camp->topic_num, $request->asof);
                $camp_id= $camp->camp_num ?? 1;
                $filter['topicNum'] = $request->topic_num;
                $filter['asOf'] = $request->asof;
                $filter['campNum'] = $camp_id;
                $livecamp = Camp::getLiveCamp($filter);
                $link = Util::getTopicCampUrl($topic->topic_num, $camp_id, $topic, $livecamp, time());
                try {
                    $dataEmail = (object) [
                        "type" => "camp",
                        "link" =>  $link,
                        "historylink" => env('APP_URL_FRONT_END') . '/camp/history/' . $topic->topic_num . '/' . $camp->camp_num,
                        "object" =>  $topic->topic_name . " / " . $camp->camp_name,
                    ];                 
                    Event::dispatch(new ThankToSubmitterMailEvent($request->user(), $dataEmail));
                } catch (Throwable $e) {
                    $data = null;
                    $status = 403;
                    $message = $e->getMessage();
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }
                $data = [
                    "camp_num" =>  $camp_id,
                ];
                $status = 200;
                $message = trans('message.success.camp_created');
            } else {
                $data = null;
                $status = 400;
                $message = trans('message.error.camp_failed');
            }
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }

  /**
    * @OA\Post(path="/get-camp-record",
     *   tags={"getCampRecord"},
     *   summary="get camp record",
     *   description="Used to get camp record.",
     *   operationId="getCampRecord",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get camp records",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="topic number is required",
     *                   required=true,
     *                   type="integer",
     *                   format="int32"
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number is required",
     *                   required=true,
     *                   type="integer",
     *                   format="int32"
     *               ),
     *               @OA\Property(
     *                   property="as_of",
     *                   description="As of filter type",
     *                   required=false,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="as_of_date",
     *                   description="As of filter date",
     *                   required=false,
     *                   type="string",
     *               )
     *        )
     *   )
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */

    public function getCampRecord(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getCampRecordValidationRules(), $this->validationMessages->getCampRecordValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['campNum'] = $request->camp_num;
        $camp=[];
        try {  
            $livecamp = Camp::getLiveCamp($filter);
            if ($livecamp) {
                $livecamp->nick_name=isset($livecamp->nickname->nick_name) ? $livecamp->nickname->nick_name : "No nickname associated";
                $topicData = Camp::getAgreementTopic($filter);
                $parentCamp = (!empty($livecamp) && !empty($topicData)) ? Camp::campNameWithAncestors($livecamp, '', $topicData->topic_name,$filter) : 'N/A';
                $camp[]=$livecamp;
                $camp = $this->resourceProvider->jsonResponse('camp-record', $camp);
                $camp[0]['parentCamps']=$parentCamp;
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $camp, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }
    
    /**
     * @OA\POST(path="/camp/allParent",
     *   tags={"Camp"},
     *   summary="Get All Parent",
     *   description="This API is use for get all parent",
     *   operationId="allParent",
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
     *                  property="topic_num",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
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
     *                type="array",
     *                @OA\Items(
     *                    @OA\Property(
     *                          property="id",
     *                          type="integer"
     *                    ),
     *                    @OA\Property(
     *                          property="topic_num",
     *                          type="integer"
     *                    ),
     *                    @OA\Property(
     *                          property="parent_camp_num",
     *                          type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="key_words",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="language",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="camp_num",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="note",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="submit_time",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="submitter_nick_id",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="go_live_time",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="objector_nick_id",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="object_time",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="object_reason",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="proposed",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="replacement",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="title",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="camp_name",
     *                           type="string" 
     *                     ),
     *                     @OA\Property(
     *                           property="camp_about_url",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="camp_about_nick_id",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="grace_period",
     *                           type="integer"
     *                     )
     *                ),
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

    public function getAllParentCamp(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getAllParentCampValidationRules(), $this->validationMessages->getAllParentCampValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $result = Camp::getAllParentCamp($request->topic_num, $request->filter, $request->asOfDate);
            if (empty($result)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            $data = $result;
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\Get(
     *     path="/camp/allAboutNickName",
     *     summary="API For Get all About Nick Name",
     *     tags={"Camp"},
     *      @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
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
     *                type="array",
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=""
     *                      ),
     *                      @OA\Property(
     *                         property="owner_code",
     *                         type="string",
     *                         example=""
     *                      ),
     *                      @OA\Property(
     *                         property="nick_name",
     *                         type="string",
     *                         example=""
     *                      ),
     *                      @OA\Property(
     *                         property="create_time",
     *                         type="string",
     *                         example=""
     *                      ),
     *                      @OA\Property(
     *                         property="private",
     *                         type="integer",
     *                         example=""
     *                      ),
     *                ),
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

    public function getAllAboutNickName(Request $request, Validate $validate)
    {

        try {

            $allNicknames = Nickname::orderBy('nick_name', 'ASC')->get();

            if (empty($allNicknames)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $allNicknames, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }
}
