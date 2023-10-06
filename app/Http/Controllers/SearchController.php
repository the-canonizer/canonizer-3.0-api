<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ElasticSearch;
use App\Models\Search;
use DB;

class SearchController extends Controller
{
    
    public function getSearchResults(Request $request)
    {
        $term = $request->get('term');
        $elasticsearch = (new Elasticsearch())->elasticsearchClient;
        $query = [
            'index' => 'canonizer_elastic_search',
            'body'  => [
                'query' => [
                    "bool" => [
                        "should" =>  [
                            [
                                "multi_match" => [
                                "query" => ".$term.",
                                "fields" => [
                                            "type_value"
                                        ]
                                    ]
                            ],
                            [
                                "multi_match" => [
                                "query" =>  ".$term.",
                                "type" => "phrase_prefix",
                                "fields" =>  [
                                            "type_value"
                                        ]
                                ]
                            ]
                        ]
                    ]
                ],
                'size' => 10,
                'from' => 0
            ],
        ];

        $result = $elasticsearch->search($query);
        return ($result);
    }


    public function importDataToElasticSearch()
    {
        //execute procedure
        DB::select("CALL sp_sync_data_to_elasticsearch");

        $elasticsearch = (new Elasticsearch())->elasticsearchClient;
        $elasticsearch->indices()->delete(['index'=>'canonizer_elastic_search']);

        $body = Search::get();
        $indexName = 'canonizer_elastic_search';
        $mapping = [
            'index' => $indexName,
            'body' => [
                'mappings' => [
                    'properties' => [
                        'id' => [
                            'type' => 'text',
                        ],
                        'type' => [
                            'type' => 'keyword',
                        ],
                        'type_value' => [
                            'type' => 'text',
                        ],
                        'topic_num' => [
                            'type' => 'integer',
                        ],
                        'camp_num' => [
                            'type' => 'integer',
                        ],
                        'statement_num' => [
                            'type' => 'integer',
                        ],
                        'nick_name_id' => [
                            'type' => 'integer',
                        ],
                        'go_live_time' => [
                            'type' => 'text',
                        ],
                        'namespace' => [
                            'type' => 'text',
                        ],
                        'link' => [
                            'type' => 'text',
                        ],
                        'support_count' => [
                            'type' => 'double',
                        ],
                        'breadcrumb' => [
                            'type' => 'nested',
                            'properties' => [
                                'camp_num' => [
                                    'type' => 'integer',
                                ],
                                'topic_num' => [
                                    'type' => 'integer',
                                ],
                                'camp_name' => [
                                    'type' => 'keyword',
                                ],
                                'topic_name' => [
                                    'type' => 'keyword',
                                ],
                                'camp_link' => [
                                    'type' => 'text',
                                ],
                                'go_live_time' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $elasticsearch->indices()->create($mapping);

        foreach($body as $key => $val){
           // echo "<pre>"; print_r((string)$val->id); exit;
            $params = [
                'index' => $indexName, // Replace with the name of your Elasticsearch index
                'id'    => $val->id, // Replace with a unique identifier for the document
                'body'  => [
                    'id' => $val->id,
                    'type_value' => $val->type_value,
                    'type' => $val->type,
                    'camp_num' => $val->camp_num,
                    'topic_num' => $val->topic_num,
                    'statement_num' =>$val->statement_num,
                    'go_live_time' => $val->go_live_time,
                    'nick_name_id' => $val->nick_name_id,
                    'support_count' => $val->support_count,
                    'namespace' => $val->namespace,
                    'link' => $val->link,
                    'breadcrumb_data' => $val->breadcrumb_data
                ],
            ];

            $elasticsearch->index($params);  //insertion
        }   

        echo 'Records inserted in elastic search are: ' . ($key+1);

    }

}
