<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorrectSupportOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:correctorder {--type= : direct or delegate }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will correct support order of existing camps.';

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

        // Get the type option or default to 'direct'
        $type = $this->option('type') ?: 'direct';

        // Define the subquery to filter records
        $subquery = DB::table('support')
            ->select('support_order', 'topic_num', 'nick_name_id')
            ->where('end', 0)
            ->groupBy('support_order', 'topic_num', 'nick_name_id')
            ->havingRaw('COUNT(DISTINCT camp_num) > 1');

        // Initialize the main query
        $query = DB::table('support')
            ->joinSub($subquery, 'filtered_support', function ($join) {
                $join->on('support.support_order', '=', 'filtered_support.support_order')
                    ->on('support.topic_num', '=', 'filtered_support.topic_num')
                    ->on('support.nick_name_id', '=', 'filtered_support.nick_name_id');
            })
            ->where('support.end', 0);

        // Apply additional filtering based on the 'type' option
        if ($type === 'direct') {
            $query->where('support.delegate_nick_name_id', 0);
        }else if($type= 'delegate'){
            $query->where('support.delegate_nick_name_id', '!=', 0);
        }

        // Order the results and fetch them
        $results = $query
            ->orderBy('support.nick_name_id', 'asc')
            ->get();

        if ($results->count() > 0) {

            // Group records by support_order and nick_name_id
            $groupedResults = $results->groupBy(function($item) {
                return $item->support_order . '-' . $item->nick_name_id . '-' . $item->topic_num;
            });
           

            foreach($groupedResults as $groupKey => $records)
            {
                    $records = $records->sortBy('start');

                    // Get the latest record (the last one in the sorted collection)
                    $latestRecord = $records->last();

                    if($type == 'direct'){
                        // Update older records
                        foreach ($records as $record) {
                            if ($record->support_id !== $latestRecord->support_id) {
                                DB::table('support')
                                    ->where('support_id', $record->support_id)
                                    ->update(['end' => $latestRecord->start]);
                            }
                        }
                    }
                    
                    //   for delegate

                  /*  if($type == 'delegate')
                    {
                        foreach ($records as $record) 
                        {
                            $dRecords = DB::table('support')
                            ->where('camp_num', $record->camp_num)
                            ->where('topic_num', $record->topic_num)
                            ->where('nick_name_id', $record->delegate_nick_name_id)->get();

                           // Try to find the record with `end = 0`
                            $delegateRecord = $dRecords->firstWhere('end', 0);

                            if (!$delegateRecord) {
                                // If no record with `end = 0`, get the latest record by the `end` field or other suitable fields
                                $delegateRecord = $dRecords->sortByDesc('end')->first();
                            }

                            DB::table('support')
                                ->where('camp_num', $record->camp_num)
                                ->where('topic_num', $record->topic_num)
                                ->where('nick_name_id', $record->nick_name_id)
                                ->update(['end' => $delegateRecord->end, 'support_order' => $delegateRecord->support_order]);

                        }
                        
                    }*/

            }
            $this->info($type . '  Support needs correct, ' . $results->count() . ' duplicated enteried for same preefrence number were found and fixed');     
        }else{
           // Output success message and exit
            $this->info('No incorrect Records found!.');
            return 0; // Exit code 0 indicates success
        }
        
    }
}
