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
            return $this->resProvider->apiJsonResponse(200, config('message.success.success'), $algorithms, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, config('message.error.exception'), '', $e->getMessage());
        }

    }

}
