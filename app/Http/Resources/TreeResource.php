<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TreeResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /* if exception happen during the tree calculation */
        if (isset($this->collection[0]['status_code']) && $this->collection[0]['status_code'] == 401) {
            return $this->collection[0];
        }

        if (count($this->collection) > 0) {
            return [
                "status_code" => 200,
                "message" => "Success",
                "data" => $this->collection,
                "error" => null
            ];
        }

        if (($this->collection->isEmpty()) || !$this->collection) {
            return [
                "status_code" => 404,
                "message" => "Tree not found",
                "data" => [],
                "error" => "Tree not found"
            ];
        }

        return [
            "status_code" => 401,
            "message" => "Unauthorized",
            "data" => [],
            "error" => $this->collection
        ];
    }
}
