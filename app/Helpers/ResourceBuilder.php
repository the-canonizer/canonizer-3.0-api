<?php

namespace App\Helpers;
class ResourceBuilder implements ResourceInterface
{
    /**
     * @param $modelType
     * @param array $data
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function jsonResponse($indexs, $data)
    {
        $res = [];
        foreach ($indexs as $index) {
            foreach ($data as $key => $row) {
                $res[$key][$index] =$row->{$index};
            }
        }
        return $res;
    }
}
