<?php

namespace App\Helpers;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticSearch
{
    public $elasticsearchClient;
    public  static $indexName = 'canonizer_elastic_search';

    public function __construct()
    {
        $host     = env('ELASTICSEARCH_HOSTS', 'localhost:9200');
        $username = env('ELASTICSEARCH_BASIC_AUTH_USERNAME', null);
        $password = env('ELASTICSEARCH_BASIC_AUTH_PASSWORD', null);
        return $this->elasticsearchClient = ClientBuilder::create()
            ->setBasicAuthentication($username, $password)
            ->setHosts(['host' => $host])
            ->build();
    }

    public static function ingestData($id, $type, $typeValue, $topicNum = 0, $campNum = 0, $link, $goLiveTime = 0, $namespace = null, $breadcrumb = '', $statementNum = '', $nickNameId = '', $supportCount = '')
    {
        $elasticsearch = (new Elasticsearch())->elasticsearchClient;
        //self::createMapping($elasticsearch);

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
             ];
 
         // Use the Bulk API to send the data in a batch
         $params = ['body' => $bulkData];
         $response = $elasticsearch->bulk($params);
         return;
 
         // Process the response if needed
        //  if ($response['errors']) {
        //      echo "Bulk indexing had errors.";
        //  } else {
        //      echo "Bulk indexing completed successfully.";
        //  }
    }

    public static function deleteData($id)
    {
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

                $delParam['ignore'] = 404;
                $response = $elasticsearch->delete($delParam);
                
               // $response = $elasticsearch->delete($delParam);
                // Process the response if needed          
        }
        return;
    }
}