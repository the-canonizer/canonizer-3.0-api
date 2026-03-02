<?php

namespace App\Helpers;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticSearch
{
    public $elasticsearchClient;
    public  static $indexName = 'canonizer_elastic_search';

    public function __construct()
    {
        if (app()->environment('testing')) {
            return;
        }
        $host     = env('ELASTICSEARCH_HOSTS', 'localhost:9200');
        $username = env('ELASTICSEARCH_BASIC_AUTH_USERNAME', null);
        $password = env('ELASTICSEARCH_BASIC_AUTH_PASSWORD', null);
        return $this->elasticsearchClient = ClientBuilder::create()
            ->setBasicAuthentication($username, $password)
            ->setHosts(['host' => $host])
            ->build();
    }

    public static function ingestData($id, 
                                    $type, 
                                    $typeValue, 
                                    $topicNum = 0, 
                                    $campNum = 0, 
                                    $link, 
                                    $goLiveTime = 0, 
                                    $namespace = null, 
                                    $breadcrumb = '', 
                                    $isLive,
                                    $isArchive,
                                    $statementNum = '', 
                                    $nickNameId = '', 
                                    $supportCount = ''
                                    )
    {
        if (app()->environment('testing')) {
            return;
        }
        $isLiveValue = filter_var($isLive, FILTER_VALIDATE_BOOLEAN); 
        $isArchiveValue = filter_var($isArchive, FILTER_VALIDATE_BOOLEAN); 
        $elasticsearch = (new Elasticsearch())->elasticsearchClient;
        $bulkData = []; // An array to accumulate data for bulk indexing
             $bulkData[] = [
                 'index' => [
                     '_index' => self::$indexName,
                     '_id' => $id,
                 ]
             ];
            $bulkData[] = [
                'id' => $id,
                'type_value' => $typeValue,
                'type' => $type,
                'camp_num' => $campNum,
                'topic_num' => $topicNum,
                'statement_num' => $statementNum,
                'go_live_time' => $goLiveTime,
                'nick_name_id' => $nickNameId,
                'support_count' => $supportCount,
                'namespace' => $namespace,
                'link' => $link,
                'breadcrumb_data' => $breadcrumb,
                'is_live'=> $isLiveValue,
                'is_archive' => $isArchiveValue
            ];
            \Log::info("nicknamesss");
        // Use the Bulk API to send the data in a batch
        $params = ['body' => $bulkData];
        \Log::info($bulkData);
        $response = $elasticsearch->bulk($params);
        \Log::info($response);
        return;

    }
    
    public static function deleteData($id)
    {
        if (app()->environment('testing')) {
            return;
        }
        $elasticsearch = (new Elasticsearch())->elasticsearchClient;
        $params = [
            'index' => self::$indexName,
            'body'  => [
                'query' => [
                    'terms' => [
                        '_id' => [$id]
                    ]
                ]
            ]
        ];      
        $response = $elasticsearch->search($params);
        if (isset($response['hits']['hits']) && !empty($response['hits']['hits']) && isset($response['hits']['total']['value'])) {
            $delParam = [
                'index' => self::$indexName,
                'id'    => $id
            ];
            $response = $elasticsearch->delete($delParam);
            // Process the response if needed          
        }
        return;
    }
}