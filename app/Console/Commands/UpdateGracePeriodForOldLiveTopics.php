<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\ActivityLog;
use App\Models\CommandHistory;
use App\Models\Topic;
use Carbon\Carbon;
use Throwable;

class UpdateGracePeriodForOldLiveTopics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:graceperiodforoldlivetopics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is useful only for updating the grace period of all older topic that have older go live time than current time.';

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

            $getTopics = Topic::where('grace_period',1)->where('go_live_time', '<', time())
                            ->where('objector_nick_id', NULL)->where('object_time', NULL)
                            ->orderBy('submit_time','DESC');

            // update the URL for those above selected activities...
            if(count($getTopics->get())) {
                $getTopics->update([
                    'grace_period' => 0
                ]);
            }

        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->parameters = $getTopics->get()->toArray() ?? [];
        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }
}
