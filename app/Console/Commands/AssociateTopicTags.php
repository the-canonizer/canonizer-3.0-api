<?php

namespace App\Console\Commands;

use App\Models\Topic;
use App\Jobs\ProcessTopicTags;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AssociateTopicTags extends Command
{
    protected $enableLogging = false;
    protected $signature = 'topic:associate-tags';
    protected $description = 'Associate topics with tags using live statements and an external API';

    public function handle()
    {
        if ($this->enableLogging) {
            Log::info("handle ==> Starting topic:associate-tags command");
        }

        try {
            $batchSize = 10; // Number of records per chunk
            $delayInSeconds = 5; // Delay between each chunk

            $query = Topic::whereDoesntHave('topicTags')
                ->whereDoesntHave('topicTagFailures')
                ->whereHas('nameSpace', function ($query) {
                    $query->where('name', 'General');
                })
                ->whereNull('objector_nick_id')
                ->where('go_live_time', '<=', time())
                ->groupBy('topic_num')
                ->latest('submit_time');
            if ($this->enableLogging) {
                Log::info("handle ==> Raw SQL query: " . $query->toSql());
                Log::info("handle ==> Query bindings: " . json_encode($query->getBindings()));
            }

            $query->chunk($batchSize, function ($topicsWithoutTags) use ($delayInSeconds) {
                if ($this->enableLogging) {
                    Log::info("handle ==> Processing batch of " . count($topicsWithoutTags) . " topics");
                }
                Log::info("handle ==> All topics on betch: " . json_encode($topicsWithoutTags));;
                dispatch(new ProcessTopicTags($topicsWithoutTags));
                if ($this->enableLogging) {
                    Log::info("handle ==> Delaying for {$delayInSeconds} seconds before the next batch");
                }
            });

            if ($this->enableLogging) {
                Log::info("handle ==> Finished topic:associate-tags command");
            }
        } catch (\Exception $e) {
            Log::error("handle ==> An error occurred: " . $e->getMessage());
        }
    }
}
