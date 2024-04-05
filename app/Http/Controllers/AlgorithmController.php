<?php

namespace App\Http\Controllers;

use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Models\Algorithm;
use Illuminate\Support\Facades\Cache;

class AlgorithmController extends Controller
{
    /**
     * Get the All Algorithms.
     *
     * @return \Illuminate\Http\Response
     */

    public function getAll()
    {

        try {
            $cacheKey = 'all_algorithm';
            $algorithms = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () {
                return Algorithm::all();
            });
            if(count($algorithms) < 1)
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.algorithms_not_found'));

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $algorithms, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }

    }

}
