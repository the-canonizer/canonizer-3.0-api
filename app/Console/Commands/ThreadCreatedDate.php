<?php

namespace App\Console\Commands;

use App\Models\Reply;
use App\Models\Thread;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

/**
 * This command will update the created_at & updated_at date of the Thread 
 * if the existing creation date of the thread is greater than the creation date of the oldest first post. 
 * Before running this command, please take the backup of the Threads table.
 * NOTE: This command should only run when we are absolutely sure to update the created_at 
 * and updated_at date of the thread
 */

class ThreadCreatedDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ThreadCreatedDate:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the thread created date if the thread created date is greater than  post created date.';

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
        $threadRecords = Thread::select('id', 'created_at', 'updated_at')->get();

        $updateRecored = 0;
        $postCount = 0;
        if ($threadRecords) {
            $postRecord = '';
            foreach ($threadRecords as $threadRecord) {
                $postRecord = Reply::select('id', 'c_thread_id', 'created_at', 'updated_at')->where('c_thread_id', $threadRecord->id)->orderBy('created_at', 'ASC')->first();
                if ($postRecord) {
                    $postCount++;
                    if ($threadRecord->created_at > $postRecord->created_at) {
                        $threadRecord->created_at = $postRecord->created_at;
                        $threadRecord->updated_at = $postRecord->updated_at;
                        $threadRecord->save();
                        $updateRecored++;
                    }
                }
            }
        }

        echo "Total Thread: " . $threadRecords->count() . "\r\n";
        echo "Total Post: " . $postCount . "\r\n";
        echo "Records for update in which thread created date greater than the post date: " . $updateRecored . "\r\n";
    }
}
