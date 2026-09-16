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
    protected $signature = 'elasticsearch:import {--force : Force full rebuild even if index has data}';
    protected $description = 'Ensures the ElasticSearch index exists and is populated. Only rebuilds if the index is missing or empty.';

    private $lockFile = '/tmp/elasticsearch_import.lock';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Prevent concurrent runs
        if (file_exists($this->lockFile)) {
            $lockPid = (int) file_get_contents($this->lockFile);
            if ($lockPid > 0 && posix_kill($lockPid, 0)) {
                Log::info('Elasticsearch import skipped: another instance is already running (PID: ' . $lockPid . ')');
                $this->info('Another import is already running. Skipping.');
                return;
            }
            // Stale lock file — previous process died
            Log::info('Removing stale lock file from PID: ' . $lockPid);
        }

        // Create lock file with current PID
        file_put_contents($this->lockFile, getmypid());

        try {
            $this->runImport();
        } finally {
            // Always remove lock file when done
            @unlink($this->lockFile);
        }
    }

    private function runImport()
    {
        $indexName = 'canonizer_elastic_search';

        try {
            $elasticsearch = (new Elasticsearch())->elasticsearchClient;
            if (!$elasticsearch) {
                Log::error('Elasticsearch import: could not create client');
                $this->error('Could not create ElasticSearch client.');
                return;
            }

            // Check if index exists and has data
            $indexExists = false;
            $docCount = 0;
            try {
                $response = $elasticsearch->indices()->exists(['index' => $indexName]);
                $indexExists = is_bool($response) ? $response : $response->asBool();
                if ($indexExists) {
                    $countResponse = $elasticsearch->count(['index' => $indexName]);
                    $docCount = $countResponse['count'] ?? 0;
                }
            } catch (\Throwable $e) {
                Log::info('Elasticsearch import: index check failed - ' . $e->getMessage());
                $indexExists = false;
            }

            // Skip if index exists with data and --force not passed
            if ($indexExists && $docCount > 0 && !$this->option('force')) {
                Log::info("Elasticsearch import skipped: index '{$indexName}' has {$docCount} documents. Use --force to rebuild.");
                $this->info("Index has {$docCount} documents. Skipping. Use --force to rebuild.");
                return;
            }

            Log::info('Starting Elasticsearch import at: ' . date('Y-m-d H:i:s') .
                ($indexExists ? " (index exists, {$docCount} docs)" : ' (index missing)') .
                ($this->option('force') ? ' [FORCED]' : ''));

            // Sync data using stored procedure
            DB::select("CALL sp_sync_data_to_elasticsearch");

            // Fetch data from MySQL
            $body = Search::get();
            $totalRecords = $body->count();
            Log::info("Fetched {$totalRecords} records from MySQL.");

            if ($totalRecords === 0) {
                Log::warning('Elasticsearch import: no records from stored procedure. Aborting to preserve existing index.');
                $this->warn('No records to import. Aborting.');
                return;
            }

            // Delete existing index only if we have data to replace it with
            if ($indexExists) {
                try {
                    $elasticsearch->indices()->delete(['index' => $indexName]);
                    Log::info("Index '{$indexName}' deleted for rebuild.");
                } catch (\Throwable $e) {
                    Log::info("Index '{$indexName}' delete failed: " . $e->getMessage());
                }
            }

            // Create index with mapping
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
                            'is_archive'    => ['type' => 'boolean'],
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
                            'breadcrumb_data' => ['type' => 'text'],
                        ]
                    ]
                ]
            ];

            $elasticsearch->indices()->create($mapping);
            Log::info("Index '{$indexName}' created successfully.");

            // Process data in chunks
            $batchSize = 250;
            $records = $body->toArray();
            $chunks = array_chunk($records, $batchSize);
            $totalIndexed = 0;

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
                            }
                            break;

                        case 'statement':
                            $campNum = $val['camp_num'];
                            $topicNum = $val['topic_num'];
                            $type_value = substr($statementValues[$val['record_id']] ?? '', 0, 500);
                            $liveTopic = Topic::getLiveTopic($topicNum);
                            if ($liveTopic) {
                                $breadcrumb_data = Search::getCampBreadCrumbData($liveTopic, $topicNum, $campNum);
                            }
                            break;
                    }

                    if (($val['type'] === 'camp' || $val['type'] === 'statement') && empty($breadcrumb_data)) {
                        continue;
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

                if (empty($bulkData)) {
                    continue;
                }

                // Send bulk request with retry logic
                $params = ['body' => $bulkData];
                $maxRetries = 3;
                $retries = 0;

                while ($retries < $maxRetries) {
                    try {
                        $response = $elasticsearch->bulk($params);
                        if (!empty($response['errors'])) {
                            throw new Exception("Bulk indexing encountered errors.");
                        }
                        $totalIndexed += count($bulkData) / 2; // Each doc has 2 entries (action + body)
                        break;
                    } catch (Exception $e) {
                        $retries++;
                        Log::warning("Bulk indexing attempt {$retries} failed: " . $e->getMessage());
                        if ($retries >= $maxRetries) {
                            Log::error('Bulk indexing failed after ' . $maxRetries . ' attempts.');
                        }
                    }
                }

                $elasticsearch->indices()->refresh(['index' => $indexName]);
            }

            Log::info("Elasticsearch import completed: {$totalIndexed} documents indexed.");
            $this->info("Import completed: {$totalIndexed} documents indexed.");
        } catch (Exception $e) {
            Log::error('Elasticsearch import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->error('An error occurred. Check logs for details.');
        }
    }
}
