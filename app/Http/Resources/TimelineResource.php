<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TimelineResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (count($this->collection) > 0) {
            $item = $this->collection[0];
            $timeline = [];
            $topicName = "";
            
            $itemArray = is_array($item) ? $item : $item->toArray();
            
            foreach ($itemArray as $key => $value) {
                if (str_starts_with($key, 'asoftime_')) {
                    $parts = explode('_', $key);
                    $asOfDate = isset($parts[1]) ? $parts[1] : '';
                    $timeline[] = [
                        'as_of_date' => $asOfDate,
                        'event' => isset($value['event']) ? $value['event'] : null
                    ];
                    
                    if (!$topicName && isset($value['payload_response'][0]['title'])) {
                        $topicName = $value['payload_response'][0]['title'];
                    }
                }
            }
            
            // Sort timeline by as_of_date
            usort($timeline, function($a, $b) {
                return (int)$a['as_of_date'] <=> (int)$b['as_of_date'];
            });

            return [
                "status_code" => 200,
                "message" => "Success",
                "error" => null,
                "data" => array_merge([
                    "topic_name" => $topicName,
                    "timeline" => $timeline
                ], $itemArray),
                "code" => 200,
                "success" => true
            ];
        }

        return [
            "data" => [],
            "code" => 404,
            "status_code" => 404,
            "success" => false,
            "message" => "Topic Timeline not found",
            "error" => "Topic Timeline not found"
        ];
    }
}
