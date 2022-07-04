<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityUser;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeleteActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylogs:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove activity logs older than 30 days';

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
        $deletedActivites=ActivityLog::where('created_at', '<',  Carbon::now()->subDays(30)->toDateTimeString())->delete();
        $deletedActivityUsers=ActivityUser::where('created_at', '<',  strtotime("-30 day"))->delete();

        Log::channel('scheduler')->info('Deleted activity logs older than 30 days: Job executed successfully, deleted ' .$deletedActivites. ' entries');
        Log::channel('scheduler')->info('Deleted activity Users older than 30 days: Job executed successfully, deleted ' .$deletedActivityUsers. ' entries');     
    }
}
