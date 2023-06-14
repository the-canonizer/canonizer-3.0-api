<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\CommandHistory;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Throwable;

class UpdateUrlOfActivityOnCampUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changecampupdatedurl:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the URL"s of activities when camp is updated.';

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
        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [],
            'started_at' => Carbon::now()->timestamp,
        ]);

        try {

            $searchCriteria = 'updated a camp';
            $getActivities = ActivityLog::where('description','LIKE','%'.$searchCriteria.'%')
                            ->whereJsonContains('properties->url', "")
                            ->orderBy('id','DESC')->get();

            // update the URL for those above selected activities...
            if(count($getActivities)) {
                foreach ($getActivities as $key => $value) {

                    $decodeProperty = json_decode($value->properties, true);
                    $link = '/camp/history/' . $decodeProperty['topic_num'] . '/' . $decodeProperty['camp_num'];
                    $decodeProperty["url"] = $link;
                    
                    $getActivities = ActivityLog::where('id', $value->id)->update(['properties' => json_encode($decodeProperty)]);
                }
            }

        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }
}
