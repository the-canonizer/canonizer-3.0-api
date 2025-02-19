<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\ElasticSearch;
use App\Models\Search;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Topic;
use App\Models\Nickname;
use App\Models\Camp;
use App\Models\Statement;

class AddExsistingDataToElasticSearch extends Command
{
    protected $signature = 'elasticsearch:import';
    protected $description = 'Creates an index and imports all searchable data from MySQL to Elasticsearch';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Starting Elasticsearch import at: ' . date('y-m-d-h-i-s'));

        try {
            // Sync data using stored procedure
            DB::select("CALL sp_sync_data_to_elasticsearch");
            
            $indexName = 'canonizer_elastic_search';
            $elasticsearch = (new Elasticsearch())->elasticsearchClient;

            // Delete index if it exists
            if ($elasticsearch->indices()->exists(['index' => $indexName])) {
                $elasticsearch->indices()->delete(['index' => $indexName]);
                Log::info("Index '{$indexName}' deleted successfully.");
            }

            // Fetch data from MySQL
            $body = Search::get();
            Log::info('Fetched ' . $body->count() . ' records from MySQL.');
            
            // Define index mapping
            $mapping = [
                'index' => $indexName,
                'body'  => [
                    "settings" => [
                        "analysis" => [
                            "tokenizer" => [
                                "my_tokenizer" => [
                                    "type" => "whitespace"
                                ]
                            ],
                            "char_filter" => [
                                "replace_special_chars" => [
                                    "type"        => "pattern_replace",
                                    "pattern"     => "[^\\p{L}\\p{N}#@$\\(\\)]+",
                                    "replacement" => " "
                                ]
                            ],
                            "analyzer" => [
                                "my_analyzer" => [
                                    "type"        => "custom",
                                    "tokenizer"   => "my_tokenizer",
                                    "char_filter" => ["replace_special_chars"],
                                    "filter"      => ["lowercase"]
                                ]
                            ]
                        ]
                    ],
                    'mappings' => [
                        'properties' => [
                            'id'            => ['type' => 'keyword'],
                            'is_live'       => ['type' => 'boolean'],
                            'type'          => ['type' => 'keyword'],
                            'type_value'    => [
                                'type'     => 'text',
                                'analyzer' => 'my_analyzer',
                                'fields'   => [
                                    'keyword' => [
                                        'type' => 'keyword',
                                        'ignore_above' => 256
                                    ]
                                ]
                            ],
                            'record_id'     => ['type' => 'integer'],
                            'topic_num'     => ['type' => 'integer'],
                            'camp_num'      => ['type' => 'integer'],
                            'statement_num' => ['type' => 'integer'],
                            'nick_name_id'  => ['type' => 'integer'],
                            'go_live_time'  => ['type' => 'long'],
                            'namespace'     => ['type' => 'keyword'],
                            'link'          => ['type' => 'keyword'],
                            'support_count' => ['type' => 'double'],
                            'breadcrumb'    => [
                                'type'       => 'nested',
                                'properties' => [
                                    'camp_num'    => ['type' => 'integer'],
                                    'topic_num'   => ['type' => 'integer'],
                                    'camp_name'   => ['type' => 'keyword'],
                                    'topic_name'  => ['type' => 'keyword'],
                                    'camp_link'   => ['type' => 'keyword'],
                                    'go_live_time'=> ['type' => 'long']
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $elasticsearch->indices()->create($mapping);
            Log::info("Index '{$indexName}' created successfully.");

            // Process data in chunks
            $batchSize = 500;
            $records = $body->toArray();
            $chunks = array_chunk($records, $batchSize);

            foreach ($chunks as $chunk) {
                $bulkData = [];
                // Pre-fetch necessary data
                $topicNames = Topic::whereIn("id", array_column($chunk, 'record_id'))->pluck('topic_name', 'id');
                $nickNames =  Nickname::whereIn("id", array_column($chunk, 'record_id'))->pluck('nick_name', 'id');
                $campNames = Camp::whereIn("id", array_column($chunk, 'record_id'))->pluck('camp_name', 'id');
                $statementValues = Statement::whereIn("id", array_column($chunk, 'record_id'))->pluck('parsed_value', 'id');
            
                foreach ($chunk as $val) {
                    $type_value = '';
                    $breadcrumb_data = "";
            
                  
                    
                    switch ($val['type']) {
                        case 'topic':
                            $type_value = $topicNames[$val['record_id']] ?? '';
                            break;
                        
                        case 'nickname':
                            $type_value = $nickNames[$val['record_id']] ?? '';
                            break;
                        
                        case 'camp':
                            $campNum = $val['camp_num'];
                            $topicNum = $val['topic_num'];
                            $type_value = $campNames[$val['record_id']] ?? '';
                            $liveTopic = Topic::getLiveTopic($topicNum);
                            if ($liveTopic) {
                                $breadcrumb_data = Search::getCampBreadCrumbData($liveTopic, $topicNum, $campNum);
                            }else{
                                $breadcrumb_data = "";
                            }
                            break;
                        
                        case 'statement':
                            $campNum = $val['camp_num'];
                            $topicNum = $val['topic_num'];
                            $type_value = $statementValues[$val['record_id']] ?? '';
                            $liveTopic = Topic::getLiveTopic($topicNum);
                            if ($liveTopic) {
                                $breadcrumb_data = Search::getCampBreadCrumbData($liveTopic, $topicNum, $campNum);
                            }else{
                                $breadcrumb_data = "";
                            }
                            break;
                    }
                    // **Fix Filtering Condition**
                    if (($val['type'] === 'camp' || $val['type'] === 'statement') && (empty($breadcrumb_data))) {
                        Log::info("Skipping Record ID: {$val['record_id']} {$val['type']} due to empty breadcrumb data.");
                        continue; // Skip this record
                    }
                    $bulkData[] = ['index' => ['_index' => $indexName, '_id' => $val['id']]];

                    $bulkData[] = [
                        'id'             => $val['id'],
                        'type_value'     => $type_value,
                        'type'           => $val['type'],
                        'camp_num'       => $val['camp_num'],
                        'topic_num'      => $val['topic_num'],
                        'statement_num'  => $val['statement_num'],
                        'go_live_time'   => $val['go_live_time'],
                        'nick_name_id'   => $val['nick_name_id'],
                        'support_count'  => $val['support_count'],
                        'namespace'      => $val['namespace'],
                        'link'           => $val['link'],
                        'is_live'        => $val['is_live'],
                        'is_archive'     => $val['is_archive'],
                        'breadcrumb_data'=> $breadcrumb_data,
                        'record_id'      => $val['record_id']
                    ];
                }
            
                // Send bulk request to Elasticsearch
                $params = ['body' => $bulkData];
                $response = $elasticsearch->bulk($params);
            
                if (!empty($response['errors'])) {
                    Log::error('Bulk indexing encountered errors:', ['errors' => json_encode($response['items'], JSON_PRETTY_PRINT)]);
                    $this->error('Some records failed to index. Check logs.');
                }
            
                // Refresh index after each batch
                $elasticsearch->indices()->refresh(['index' => $indexName]);
            }
            
            Log::info('Bulk indexing completed successfully.');
            $this->info('Bulk indexing completed successfully.');
        } catch (Exception $e) {
            Log::error('Elasticsearch import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->error('An error occurred. Check logs for details.');
        }

        Log::info('Elasticsearch import finished at: ' . date('y-m-d-h-i-s'));
    }
}
