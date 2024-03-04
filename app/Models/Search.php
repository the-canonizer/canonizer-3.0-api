<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ElasticSearch;

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
        'go_live_time' => 'string',
        'nick_name_id' => 'integer',
        'namespace' => 'string',
        'link' => 'string',
        'statement_num' => 'integer',
        'breadcrum_data' => 'json',
        'support_count' => 'double'
    ];

    protected $fillable = ['id', 'type', 'type_value','topic_num', 'camp_num', 'go_live_time', 'nick_name_id', 'namespace', 'link', 'statement_num', 'breadcrum_data', 'support_count'];

    public static function getSearchData($search, $type, $size = 25, $from = 1)
    {
        $elasticsearch = (new Elasticsearch())->elasticsearchClient;
        $size = (intval($size) ?: 25);
        $from = $size * ((intval($from) ?: 1) - 1);

        $searchFields = ['type_value'];
        //$excludesColumn = ["id","type","breadcrumb"];


        $indexName = 'canonizer_elastic_search';
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
                        'filter' => [
                            [
                                'terms' => [
                                    'type' => $type, // Use the custom type value here
                                ],
                            ],
                        ],
                    ],
                ],
                'size' => $size, // Use the custom size value here
                'from' => $from, // Use the custom from value here
            ],
        ]);

        if (isset($response['hits']['hits']) && isset($response['hits']['total']['value'])) {
            $parsedResponse = $response['hits']['hits'];
            $totalResponse = $response['hits']['total']['value'];
        
            $data = [
                'data' => collect($parsedResponse)->pluck('_source'),
                'type' => $type,
                'count' => $totalResponse,
            ];
        
            return $data;
        } else {
            // Handle the case where the Elasticsearch response doesn't contain the expected data.
            $data = [
                'data' => [],
                'type' => '',
                'count' => 0
            ]; 
            
            return $data;
        }

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

        // Convert the JSON object to a JSON array
        $jsonArray = array_values($tempdata);

        // Encode the resulting JSON array
        $jsonString = json_encode($jsonArray, JSON_UNESCAPED_SLASHES);
        return $jsonString;
    }
}
