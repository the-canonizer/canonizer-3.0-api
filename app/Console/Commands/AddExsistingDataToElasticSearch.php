<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\ElasticSearch;
use App\Models\Search;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;

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
        Log::info('Starting Elasticsearch import at: ' . time());

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
                                ],
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
                            ],
                        ],
                    ],
                    'mappings' => [
                        'properties' => [
                            'id'            => ['type' => 'keyword'],
                            'is_live'       => ['type' => 'boolean'],
                            'type'          => ['type' => 'keyword'],
                            'type_value'    => [
                                'type'     => 'text',
                                'analyzer' => 'my_analyzer',
                                'fields'   => ['keyword' => ['type' => 'keyword']]
                            ],
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
                                    'go_live_time'=> ['type' => 'long'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            // Create a new index
            $elasticsearch->indices()->create($mapping);
            Log::info("Index '{$indexName}' created successfully.");

            // Prepare bulk data
            $bulkData = [];
            foreach ($body as $val) {
                $bulkData[] = [
                    'index' => [
                        '_index' => $indexName,
                        '_id'    => $val->id,
                    ]
                ];
                $bulkData[] = [
                    'id'             => $val->id,
                    'type_value'     => $val->type_value,
                    'type'           => $val->type,
                    'camp_num'       => $val->camp_num,
                    'topic_num'      => $val->topic_num,
                    'statement_num'  => $val->statement_num,
                    'go_live_time'   => $val->go_live_time,
                    'nick_name_id'   => $val->nick_name_id,
                    'support_count'  => $val->support_count,
                    'namespace'      => $val->namespace,
                    'link'           => $val->link,
                    'is_live'        => $val->is_live,
                    'is_archive'     => $val->is_archive,
                    'breadcrumb_data'=> $val->breadcrumb_data
                ];
            }

            // Use the Bulk API for batch indexing
            $params   = ['body' => $bulkData];
            $response = $elasticsearch->bulk($params);

            if ($response['errors']) {
                Log::error('Bulk indexing had errors:', $response['items']);
                $this->error('Bulk indexing had errors. Check logs.');
            } else {
                Log::info('Bulk indexing completed successfully.');
                $this->info('Bulk indexing completed successfully.');
            }

        } catch (Exception $e) {
            Log::error('Elasticsearch import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->error('An error occurred. Check logs for details.');
        }

        Log::info('Elasticsearch import finished at: ' . time());
    }
}
