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
            $tree = $this->collection[0];
            $topicNumber = isset($tree['topic_id']) ? $tree['topic_id'] : (isset($tree->topic_id) ? $tree->topic_id : null);
            
            $topicName = "";
            if ($topicNumber) {
                $topic = \App\Models\Topic::where('topic_num', (int)$topicNumber)
                    ->where('objector_nick_id', NULL)
                    ->where('go_live_time', '<=', time())
                    ->orderBy('submit_time', 'desc')
                    ->first();
                $topicName = $topic ? $topic->topic_name : '';
            }

            $timelineData = [];
            $treeArray = is_array($tree) ? $tree : $tree->toArray();
            foreach($treeArray as $key => $val) {
                if(str_starts_with($key, 'asoftime_')) {
                    $parts = explode('_', $key);
                    $timelineData[] = [
                        'as_of_date' => (int)$parts[1],
                        'event' => $val['event']
                    ];
                }
            }
            // Sort by as_of_date
            usort($timelineData, function($a, $b) {
                return $a['as_of_date'] <=> $b['as_of_date'];
            });

            return [
                "status_code" => 200,
                "message" => "Success",
                "error" => null,
                "data" => [
                    "topic_name" => $topicName,
                    "timeline" => $timelineData
                ]
            ];
        }

        return [
            "status_code" => 404,
            "message" => "Topic Timeline not found",
            "error" => "Topic Timeline not found",
            "data" => []
        ];
    }
}
