<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\DeleteActivityLogs',
        'App\Console\Commands\UpdateS3FilePathInStatementTable',
        'App\Console\Commands\UpdateS3FilePathInUploadTable',
        'App\Console\Commands\ReverseS3PathToFilePath',
        'App\Console\Commands\UpdateParseStatement',
        'App\Console\Commands\DeleteURLFromActivityLogsCommand',
        'App\Console\Commands\ThreadCreatedDate',
        'App\Console\Commands\UpdateNamespaceToCanon',
        'App\Console\Commands\UpdateUrlOfActivityOnCampUpdate',
        'App\Console\Commands\UpdateActivityUrlOnCampObject',
        // 'App\Console\Commands\UpdateDelegateSupportOrderForATopic' // Disable this command because this command is for specific issue occured on production #784.
        'App\Console\Commands\AddExsistingDataToElasticSearch',
        'App\Console\Commands\UpdateGracePeriodForOldLiveTopics',
        'App\Console\Commands\UpdateUserIdFromOwnerCode',
        'App\Console\Commands\UpdateUserLastNameFromFullName'
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('activitylogs:delete')->dailyAt('00:01');
    }
}
