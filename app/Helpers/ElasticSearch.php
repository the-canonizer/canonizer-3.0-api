<?php

namespace App\Helpers;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticSearch
{
    public $elasticsearchClient;
    public  static $indexName = 'canonizer_elastic_search';
    private static $indexChecked = false;

    public function __construct()
    {
        $host     = env('ELASTICSEARCH_HOSTS', 'localhost:9200');
        $username = env('ELASTICSEARCH_BASIC_AUTH_USERNAME', null);
        $password = env('ELASTICSEARCH_BASIC_AUTH_PASSWORD', null);
        $clientBuilder = ClientBuilder::create()->setHosts([$host]);
        if (!empty($username)) {
            $clientBuilder->setBasicAuthentication($username, $password);
        }
        return $this->elasticsearchClient = $clientBuilder->build();
    }

    /**
     * Ensure the ElasticSearch index exists, creating it with proper mappings if missing.
     * Only checks once per request to avoid repeated calls.
     */
    public static function ensureIndexExists($elasticsearch)
    {
        if (self::$indexChecked || $elasticsearch === null) {
            return;
        }

        try {
            $exists = $elasticsearch->indices()->exists(['index' => self::$indexName])->asBool();
            if (!$exists) {
                \Log::info("ElasticSearch index '" . self::$indexName . "' not found. Creating...");
                $mapping = [
                    'index' => self::$indexName,
                    'body'  => [
                        'settings' => [
                            'analysis' => [
                                'tokenizer' => [
                                    'my_tokenizer' => [
                                        'type' => 'whitespace'
                                    ]
                                ],
                                'char_filter' => [
                                    'replace_special_chars' => [
                                        'type'        => 'pattern_replace',
                                        'pattern'     => '[^\\p{L}\\p{N}#@$\\(\\)]+',
                                        'replacement' => ' '
                                    ]
                                ],
                                'analyzer' => [
                                    'my_analyzer' => [
                                        'type'        => 'custom',
                                        'tokenizer'   => 'my_tokenizer',
                                        'char_filter' => ['replace_special_chars'],
                                        'filter'      => ['lowercase']
                                    ]
                                ]
                            ]
                        ],
                        'mappings' => [
                            'properties' => [
                                'id'             => ['type' => 'keyword'],
                                'is_live'        => ['type' => 'boolean'],
                                'is_archive'     => ['type' => 'boolean'],
                                'type'           => ['type' => 'keyword'],
                                'type_value'     => [
                                    'type'     => 'text',
                                    'analyzer' => 'my_analyzer',
                                    'fields'   => [
                                        'keyword' => [
                                            'type'         => 'keyword',
                                            'ignore_above' => 256
                                        ]
                                    ]
                                ],
                                'record_id'      => ['type' => 'integer'],
                                'topic_num'      => ['type' => 'integer'],
                                'camp_num'       => ['type' => 'integer'],
                                'statement_num'  => ['type' => 'integer'],
                                'nick_name_id'   => ['type' => 'integer'],
                                'go_live_time'   => ['type' => 'long'],
                                'namespace'      => ['type' => 'keyword'],
                                'link'           => ['type' => 'keyword'],
                                'support_count'  => ['type' => 'double'],
                                'breadcrumb_data' => ['type' => 'text'],
                            ]
                        ]
                    ]
                ];
                $elasticsearch->indices()->create($mapping);
                \Log::info("ElasticSearch index '" . self::$indexName . "' created successfully.");
            }
        } catch (\Exception $e) {
            \Log::error("ElasticSearch ensureIndexExists error: " . $e->getMessage());
        }

        self::$indexChecked = true;
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
        $isLiveValue = filter_var($isLive, FILTER_VALIDATE_BOOLEAN);
        $isArchiveValue = filter_var($isArchive, FILTER_VALIDATE_BOOLEAN);

        try {
            $elasticsearch = (new Elasticsearch())->elasticsearchClient;
            self::ensureIndexExists($elasticsearch);
            $bulkData = [];
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
            // Use the Bulk API to send the data in a batch
            $params = ['body' => $bulkData];
            $response = $elasticsearch->bulk($params);
        } catch (\Exception $e) {
            \Log::error("ElasticSearch ingestData error: " . $e->getMessage());
        }
        return;

    }

    public static function deleteData($id)
    {
        try {
            $elasticsearch = (new Elasticsearch())->elasticsearchClient;
            self::ensureIndexExists($elasticsearch);
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
            }
        } catch (\Exception $e) {
            \Log::error("ElasticSearch deleteData error: " . $e->getMessage());
        }
        return;
    }
}
