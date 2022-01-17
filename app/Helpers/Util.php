<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class Util
{
    public function validate(Request $request , $rules, $messages = []) {
        $validator = Validator::make($request->all(), $rules, $messages);
        if($validator->fails()){
            return (object)[
                "status_code" => 400,
                "message"     => "The given data was invalid.",
                "error"       => $validator->errors(),
                "data"        => null
            ];
        }

        return true;
    }

    public function httpPost($url, $data) {
        $response = Http::asForm()->post($url, $data);

        $status = $response->status();

        switch($status){
            case 200:
                return (object)[
                    "status_code" => 200,
                    "message"     => "Success",
                    "error"       => null,
                    "data"        => $response->json()
                ];
            case 401:
                return (object)[
                    "status_code" => 401,
                    "message"     => "Unauthenticated",
                    "error"       => null,
                    "data"        => null
                ];
            default :
                return (object)[
                    "status_code" => 400,
                    "message"     => "Something went wrong",
                    "error"       => null,
                    "data"        => null
                ];
        }
    }
}
