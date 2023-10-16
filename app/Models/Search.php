<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ElasticSearch;

class Search extends Model
{
   
    protected $table = 'elasticsearch_data';

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
        $excludesColumn = ["id","type","breadcrumb"];


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
}
