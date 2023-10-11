<?php

namespace App\Http\Controllers;

use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Models\Algorithm;

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
            $algorithms = Algorithm::all();

            if(count($algorithms) < 1)
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.algorithms_not_found'));

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $algorithms, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }

    }

}
