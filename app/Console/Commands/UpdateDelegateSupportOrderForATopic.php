<?php

namespace App\Console\Commands;

use App\Models\Support;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\CommandHistory;
use Carbon\Carbon;
use Throwable;
use Exception;

class UpdateDelegateSupportOrderForATopic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatesupportorderfortopic:update {topic_num} {nick_name_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the support order only for delegate and where nickname id is specific';

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
        $topic_num = $this->argument('topic_num') ?? NULL;
        $nick_name_id = $this->argument('nick_name_id') ?? NULL;

        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [
                'topic_num' => $topic_num,
                'nick_name_id' => $nick_name_id
            ],
            'started_at' => Carbon::now()->timestamp
        ]);

        try {
            if (!empty($topic_num) && !empty($nick_name_id)) {

                if($topic_num!==88 && $nick_name_id != 55 ) {
                    throw new Exception("Command is only for specific record");
                }

                $whereConditions = [
                    'topic_num' => $topic_num,
                    'nick_name_id' => $nick_name_id,
                    'end' => 0,
                    'delegate_nick_name_id' => 1
                ];

                $getRecordsToUpdate = Support::where($whereConditions)->orderBy('support_order', 'ASC')->get();

                Log::info("UpdateDelegateSupportOrderCommandEnded command started");
                
                // log the previos records of database as backup
                Log::info("Backup data before change");
                Log::info($getRecordsToUpdate->toJson());

                foreach ($getRecordsToUpdate as $key => $item) {
                    // keep the record updated using current item
                    // here get the data on base of delegate nickname id, camp number, topic number and end = 0
                    // update the record one by one on base of current item
                    $where = [
                        'topic_num' => $item->topic_num,
                        'camp_num' => $item->camp_num,
                        'end' => 0,
                        'nick_name_id' => $item->delegate_nick_name_id
                    ];

                    $getSupportOfDelegator = Support::where($where)->first();
                    
                    // Update the item and log the item below that is updated
                    if (!empty($getSupportOfDelegator)) {
                        $item->update(['support_order'=>$getSupportOfDelegator->support_order]);                        
                        Log::info("Record that is updated by order is below");
                        Log::info($item);
                        Log::info("Update done");
                    }
                }

                Log::info("UpdateDelegateSupportOrderCommandEnded command ended");
                echo "Command execution done";
            }
        } catch (Throwable $e) {
            $commandHistory->error_output = json_encode($e);
            $commandHistory->save();
            echo "Error in update " . $e->getMessage() . "\r\n";
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();

    }
}
