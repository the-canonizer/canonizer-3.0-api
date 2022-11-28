<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseInterface;
use App\Http\Resources\ErrorResource;
use App\Http\Request\Validate;
use App\Models\Video;

class VideoController extends Controller
{
    public function __construct(ResponseInterface $respProvider)
    {
        $this->resProvider = $respProvider;
    }

    public function getVideos(Request $request)
    {
        try {
            $videos = (new Video())->with('resolutions')->get();

            $videos = collect($videos)->map(function ($video) {
                $video->resolutions = collect($video->resolutions)->map(function ($resolution) use ($video) {
                    $resolution->link = $video->link . '_' . $resolution->resolution;
                    unset($resolution->resolution);
                    return $resolution;
                });
                unset($video->link);
                return $video;
            });

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'),  $videos, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}
