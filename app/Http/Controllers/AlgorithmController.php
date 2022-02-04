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
            $res = (object) [
                "status_code" => 200,
                "message" => "Success",
                "error" => null,
                "data" => $algorithms,
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
