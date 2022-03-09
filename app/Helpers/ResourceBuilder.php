<?php

namespace App\Helpers;

use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;

class ResourceBuilder implements ResourceInterface
{
    /**
     * @param $modelType
     * @param array $data
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function jsonResponse($modelType, $data)
    {
        $res = [];

        if($modelType == 'ad') {
            foreach($data as $row) {
                $res[] =  [
                    "client_id" => $row->client_id,
                    "slot" => $row->slot,
                    "format" => $row->format,
                    "test_ad" => $row->adtest == 0 ? 'off' : 'on', 
                    "is_responsive" => $row->is_responsive == 0 ? false : true, 
                    "status" => $row->status == 0 ? false : true
                ];
            }   
        }
        else if($modelType == 'image') {
            foreach($data as $row) {
                $res[] = [
                    'title' => $row->title,
                    'description' => $row->description,
                    'route' => $row->route,
                    'image_url' => $row->url
                ];
            }
        }
        else if($modelType == 'Statement') {
            foreach($data as $row) {
                $res[] = [
                    'id' => $row->id,
                    'value' => $row->value,
                    'note' => $row->note,   
                ];
            }
        }
        else if($modelType == 'NewsFeed') {
            foreach($data as $row) {
                $res[] = [
                    'id' => $row->id,
                    'display_text' => $row->display_text,
                    'link' => $row->link,
                    'available_for_child' => $row->available_for_child,
                    'link' => $row->link,  
                ];
            }
        }
        
        return $res;
    }
}
