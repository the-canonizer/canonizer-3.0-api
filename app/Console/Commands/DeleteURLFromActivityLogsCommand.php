<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DeleteURLFromActivityLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:delete-base-url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will find and remove all the base URI from the activity logs table.';

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
        $ActivitiesWithBaseURL = (new ActivityLog())->where('properties->url', 'like', 'http%')->get();

        $ActivitiesWithBaseURL = collect($ActivitiesWithBaseURL)->each(function ($item, $key) {
            
            $item->properties = json_decode($item->properties);
            $parsedURL = parse_url($item->properties->url);

            if (!empty($parsedURL['path'])) {
                $item->properties->url = $parsedURL['path'];
                if (isset($parsedURL['query']) && !empty($parsedURL['query'])) {
                    $item->properties->url .= '?' . $parsedURL['query'];
                }
            }

            $item->properties = json_encode($item->properties);
            $item->save();
            return true;
        });
    }
}
