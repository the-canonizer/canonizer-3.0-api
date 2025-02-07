<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ElasticSearch;
use DB;
use App\Models\Topic;
use App\Models\Camp;
use App\Helpers\SupportAndScoreCount;
use App\Facades\Util;

class Search extends Model
{
    protected $table = 'elasticsearch_data';
    public $timestamps = false;

    protected $casts = [
        'id' => 'string',
        'type' => 'string',
        'type_value' => 'string',
        'topic_num' => 'integer',
        'camp_num' => 'integer',
        'go_live_time' => 'integer',
        'nick_name_id' => 'integer',
        'namespace' => 'string',
        'link' => 'string',
        'statement_num' => 'integer',
        'breadcrum_data' => 'json',
        'support_count' => 'double',
        'is_live' => 'boolean',
        'is_archive' => 'boolean'
    ];

    protected $fillable = ['id', 'type', 'type_value','topic_num','camp_num', 'go_live_time', 'nick_name_id', 'namespace', 'link','statement_num', 'breadcrum_data', 'support_count'];
    
    public static function getSearchData($search, $type, $size, $from, $isLive, $asof = 'default', $asofdate = '')
    {
        $elasticsearch = (new Elasticsearch())->elasticsearchClient;
        $size = intval($size) ? $size: 20 ;
        $from = $size * ((intval($from) ? : 1) - 1);
        $searchFields = ['type_value'];
        $indexName = 'canonizer_elastic_search';
        $rangeValue = '';
        if($asof === 'bydate'){
            $rangeValue = intval($asofdate);
        }
        $response = $elasticsearch->search([
            'index' => $indexName,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'bool' => [
                                    'should' => [
                                        [
                                            'multi_match' => [
                                                'query' => $search, // Use the custom query value here
                                                'fields' => $searchFields,
                                            ],
                                        ],
                                        [
                                            'multi_match' => [
                                                'query' => $search, // Use the custom query value here
                                                'type' => 'phrase_prefix',
                                                'fields' => $searchFields,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'filter' => array_merge(
                            [
                                [
                                    'term' => [
                                        'is_live' => $isLive, // Use the custom type value here
                                    ],
                                ],
                                [
                                    'term' => [
                                        'is_archive' => false, // Use the custom type value here
                                    ],
                                ],
                                [
                                    'terms' => [
                                        'type' => $type, // Use the custom type value here
                                    ],
                                ],
                            ],
                            // Add the range filter conditionally
                            $rangeValue ? [
                                [
                                    'range' => [
                                        'go_live_time' => [
                                            'lte' => $rangeValue, // Ensure it's an integer
                                        ],
                                    ],
                                ]
                            ] : []
                        ),
                    ],
                ],
                'size' => $size, // Use the custom size value here
                'from' => $from, // Use the custom from value here
                'aggs' => [
                    'type_counts' => [
                        'terms' => [
                            'field' => 'type' // No size parameter specified
                        ],
                    ],
                ],
                'sort' => [
                    [
                        'topic_num' => [
                            'order' => 'desc' // Sorting in descending order
                        ]
                    ]
                ],
            ],
        ]);
     
        if (isset($response['hits']['hits']) && isset($response['hits']['total']['value'])) {
            $parsedResponse = $response['hits']['hits'];
            $totalResponse = $response['hits']['total']['value'];
            $typeCounts = [];
            if (isset($response['aggregations']['type_counts']['buckets'])) {
                foreach ($response['aggregations']['type_counts']['buckets'] as $bucket) {
                    $typeCounts[$bucket['key']] = $bucket['doc_count'];
                }
            }
            $data = [
                'data' => collect($parsedResponse)->pluck('_source'),
                'type' => $type,
                'count' => $totalResponse,
                'type_counts' => $typeCounts, // Include counts per type
            ];
        } else {
            // Handle the case where the Elasticsearch response doesn't contain the expected data.
            $data = [
                'data' => [],
                'type' => '',
                'count' => 0,
                'type_counts' => 0
            ]; 
        }

        return $data;

    }

    public static function processResults($data,$type)
    {
        $topic = [];
        foreach($data as $dt)
        {
            $temp['title'] = $dt->topic_name;
            $temp['topic_num'] = $dt->topic_num;
            $temp['camp_num'] = 1;
            $temp['camp_name'] = 'Agreement';
            $temp['link'] = Topic::topicLink($dt->topic_num, 1, $dt->topic_name, 'Agreement', true);
            array_push($topic, $temp);
        }
        return $topic;
    }
    /**
     * Return breadcrum data for elastic search 
     */
    public static function getCampBreadCrumbData($liveTopic, $topicNum, $campNum)
    {
        $filter['topicNum'] = $topicNum;
        $filter['campNum'] = $campNum;
        $livecamp = Camp::getLiveCamp($filter);
        $breadcrumb = array_reverse(Camp::campNameWithAncestors($livecamp, $filter));
        $data = [];
        $tempdata = [];
        foreach($breadcrumb as $k => $bd)
        {
            $temp[$k+1] = [
                'camp_num' =>  $bd['camp_num'],
                'camp_link' => Camp::campLink($bd['topic_num'], $bd['camp_num'], $liveTopic->topic_name, $bd['camp_name'], true),
                'camp_name' => $bd['camp_name'],
                'topic_num' => $bd['topic_num'],
                'topic_name' => $liveTopic->topic_name
            ];
            $tempdata[$k] = $temp;
        }

        $jsonArray = array_values($tempdata);
        // Encode the resulting JSON array
        $jsonString = json_encode($jsonArray, JSON_UNESCAPED_SLASHES);
        return $jsonString;
    }

    public static function advanceTopicFilterByNickname($nickIds, $query)
    {
        $results = DB::table('topic as t')
            ->select('t.topic_num', 't.topic_name', 't.id')
            ->join(DB::raw('(
                    SELECT topic_num 
                    FROM support 
                    WHERE nick_name_id IN (' . implode(',', $nickIds) . ')
                    AND end = 0 
                    AND camp_num = 1
                ) AS s'), 't.topic_num', '=', 's.topic_num')
            ->join(DB::raw('(
                    SELECT topic_num,
                        MAX(go_live_time) AS live_time
                    FROM topic
                    WHERE objector_nick_id IS NULL
                    AND go_live_time <= UNIX_TIMESTAMP(NOW())
                    GROUP BY topic_num
                ) AS b'), function ($join) use ($query) {
                    $join->on('t.topic_num', '=', 'b.topic_num')
                        ->where('t.go_live_time', '=', DB::raw('b.live_time'))
                        ->where('t.topic_name', 'like', '%' . $query . '%');
                })
            ->get();

            $topic = self::processResults($results,'topic');

        return $topic;
    }

    public static function advanceCampFilterByNickname($nickIds, $query)
    {
        $results = DB::table('camp as a')
                    ->select('a.camp_name', 'a.topic_num', 'a.camp_num', 'a.go_live_time')
                    ->join(DB::raw('(SELECT topic_num, camp_num
                                    FROM support 
                                    WHERE nick_name_id IN (' . implode(',', $nickIds) . ')
                                    AND end = 0 
                                    AND camp_num != 1) as s'), function ($join) {
                        $join->on('a.topic_num', '=', 's.topic_num')
                            ->on('a.camp_num', '=', 's.camp_num');
                    })
                    ->join(DB::raw('(SELECT topic_num, camp_num, MAX(go_live_time) AS live_time
                                    FROM camp
                                    WHERE objector_nick_id IS NULL
                                    AND go_live_time <= UNIX_TIMESTAMP(NOW())
                                    AND grace_period = 0
                                    GROUP BY topic_num, camp_num) as b'), function ($join) {
                        $join->on('a.topic_num', '=', 'b.topic_num')
                            ->on('a.camp_num', '=', 'b.camp_num');
                    })
                    ->where('a.is_archive', 0)
                    ->where('a.go_live_time', DB::raw('b.live_time'))
                    ->where('a.camp_name', 'like', '%' . $query . '%')
                    ->get();
        $camps = [];
        foreach($results as $result)
        {
            $topicNum = $result->topic_num;
            $campNum  = $result->camp_num;
            $liveTopic = Topic::getLiveTopic($topicNum);  
            $checkTopicNum = Topic::where("topic_num", $topicNum)->first();
            if($checkTopicNum){
                $breadcrumb = self::getCampBreadCrumbData($liveTopic, $topicNum, $campNum);
                $temp['camp_num'] = $result->camp_num;
                $temp['topic_num'] = $topicNum;
                $temp['title'] = $result->camp_name;
                $temp['link'] = Camp::campLink($topicNum, $result->camp_num, $liveTopic->topic_name, $result->camp_name, true);
                $temp['breadcrumb'] = $breadcrumb;
                array_push($camps, $temp);
            }
        }
        return $camps;
    }

    public static function advanceSearchFilter($type, $asof, $asofdate, $search, $pageNumber, $pageSize)
    {
        $isLive = ($asof === 'bydate');
        $result = self::getSearchData($search, [$type], $pageSize, $pageNumber, $isLive, $asof, $asofdate);
        return [
            'data'  => $result['data'] ?? [],  
            'total' => $result['count'] ?? 0,  
        ];
    }

    public static function createOrUpdate($id, $type, $typeValue, $topicNum = 0, $campNum = 0, $link, $goLiveTime = 0, $namespace = null, $breadcrumb = '', $statementNum = '', $nickNameId = '', $supportCount = '')
    {       
        if($type == 'nickname'){
            $queryArray = ['type' => 'nickname','nick_name_id' => $nickNameId];  
        }else if($type == 'statement'){
            $queryArray = ['type' => 'statement','statement_num'=>$statementNum];   
        }else{
            $queryArray = ['type' => $type,'topic_num' => $topicNum, 'camp_num' => $campNum];   
        }
        $modelEvent = 'create';
        $search = Search::updateOrCreate($queryArray);
        if(!empty($search)){
            $modelEvent = 'update';
        }
        $search->id = $id;
        $search->type = $type;
        $search->type_value = $typeValue;
        $search->topic_num = $topicNum;
        $search->camp_num = $campNum;
        $search->statement_num = $statementNum;
        $search->nick_name_id = $nickNameId;
        $search->go_live_time = $goLiveTime;
        $search->namespace = $namespace;
        $search->link = $link;
        $search->breadcrumb_data = $breadcrumb;
        $search->support_count = $supportCount;
        if($modelEvent == 'update'){
            $data = [
                'id' => $id,
                'type' => $type,
                'type_value' => $typeValue,
                'topic_num' => $topicNum,
                'camp_num' => $campNum,
                'statement_num' => $statementNum,
                'go_live_time' => $goLiveTime,
                'namespace' => $namespace,
                'link' => $link,
                'breadcrumb_data' => json_encode($breadcrumb),
                'nick_name_id' => $nickNameId,
                'support_count' => $supportCount
            ];
            Search::where($queryArray)
                    ->update($data);
            return;
        }
        $search->save();
        return;
    }

    public static function deleteRecordIfExist($id)
    {
        Search::where(['id'=>$id])->delete();
        return;
    }
}
