<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Helpers\ResponseInterface;
use App\Models\EmbeddedCodeTracking;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Request\ValidationMessages;

class EmbeddedCodeController extends Controller
{

    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
        $this->resProvider = $resProvider;
    }

    public function createEmbeddedCodeTracking(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getEmbeddedCodeTrackingRules(), $this->validationMessages->getEmbeddedCodeTrackingMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $input = [
                "url" => $request->url,
                "ip_address" => $request->ip_address,
                "user_agent" => $request->user_agent,
            ];
            $embeddedCodeTracking = EmbeddedCodeTracking::create($input);
            if ($embeddedCodeTracking) {
                $status = 200;
                $message = trans('message.success.success');
            }
            return $this->resProvider->apiJsonResponse($status, $message, $embeddedCodeTracking, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }
}
