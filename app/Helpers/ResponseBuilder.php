<?php

namespace App\Helpers;

use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;

class ResponseBuilder implements ResponseInterface
{
    /**
     * @param $code
     * @param $message
     * @param array $data
     * @param string $error
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function apiJsonResponse($code, $message, $data, $error = null)
    {
        $res = (object)[
            "status_code" => $code,
            "message"     => $message,
            "error"       => $error,
            "data"        => $data
        ];

        if ( $error ) {
            return (new ErrorResource($res))->response()->setStatusCode($code);
        }
        else {
            return (new SuccessResource($res))->response()->setStatusCode($code);
        }
    }
}
