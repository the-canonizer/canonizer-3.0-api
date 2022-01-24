<?php

namespace App\Http\Controllers;

use App\Http\Resources\SuccessResource;
use App\Http\Resources\ErrorResource;
use App\Models\SocialMediaLink;

class SocialMediaLinkController extends Controller
{
    public function getLinks()
    {
        try {
            $socialMediaLinks = SocialMediaLink::all();
            $res = (object) [
                "status_code" => 200,
                "message" => "Success",
                "error" => null,
                "data" => $socialMediaLinks,
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);
        } catch (\Throwable $e) {
            $res = (object) [
                "status_code" => 400,
                "message" => "Something went wrong",
                "error" => $e->getMessage(),
                "data" => null,
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }

    }
}
