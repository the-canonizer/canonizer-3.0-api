<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\ElasticSearch;
use App\Models\Search;
use DB;

class AddExsistingDataToElasticSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will create and index and import all searchable data from mysql to elastic search database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
         //execute procedure
         //DB::select("CALL sp_sync_data_to_elasticsearch");
         $indexName = 'canonizer_elastic_search';
         $elasticsearch = (new Elasticsearch())->elasticsearchClient;

         $elasticsearch->indices()->delete(['index' => $indexName]);
         $body = Search::get();       
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

         $bulkData = []; // An array to accumulate data for bulk indexing

        foreach ($body as $key => $val) {
            $bulkData[] = [
                'index' => [
                    '_index' => $indexName,
                    '_id' => $val->id,
                ]
            ];
            $bulkData[] = [
                'id' => $val->id,
                'type_value' => $val->type_value,
                'type' => $val->type,
                'camp_num' => $val->camp_num,
                'topic_num' => $val->topic_num,
                'statement_num' => $val->statement_num,
                'go_live_time' => $val->go_live_time,
                'nick_name_id' => $val->nick_name_id,
                'support_count' => $val->support_count,
                'namespace' => $val->namespace,
                'link' => $val->link,
                'breadcrumb_data' => $val->breadcrumb_data
            ];
        }

        // Use the Bulk API to send the data in a batch
        $params = ['body' => $bulkData];
        $response = $elasticsearch->bulk($params);

        // Process the response if needed
        if ($response['errors']) {
            echo "Bulk indexing had errors.";
        } else {
            echo "Bulk indexing completed successfully.";
        }
 
         echo 'Records inserted in elastic search are: ' . ($key+1);
    }
}
