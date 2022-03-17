<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Http\Resources\SuccessResource;
use App\Http\Request\ValidationMessages;
use App\Events\ThankToSubmitterMailEvent;
use App\Events\ForgotPasswordSendOtpEvent;
use App\Http\Resources\Authentication\UserResource;

class CampController extends Controller
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

        $validationErrors = $validate->validate($request, $this->rules->getCampStoreValidationRules(), $this->validationMessages->getCampStoreValidationMessages());

        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {

            $current_time = time();

            ## check if mind_expert topic and camp abt nick name id is null then assign nick name as about nickname ##
            if ($request->topic_num == '81' && !isset($request->camp_about_nick_id)) {
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
                $livecamp = Camp::getLiveCamp($topic->topic_num, $camp_id, $request->asof);
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
                    $status = 403;
                    $message = $e->getMessage();
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }
                $status = 200;
                $message = trans('message.success.camp_created');
            } else {
                $status = 400;
                $message = trans('message.error.cqmp_failed');
            }
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }
}
