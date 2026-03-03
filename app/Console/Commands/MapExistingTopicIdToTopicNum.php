<?php

namespace App\Console\Commands;

use App\Models\Topic;
use App\Models\TopicTag;
use Illuminate\Console\Command;

class MapExistingTopicIdToTopicNum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'topic-tags:update-topic-id-to-topic-num';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to map existing topic id to topic number';

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
        $this->withProgressBar(TopicTag::where('topic_num', 0)->get(), function (TopicTag $topicTag) {          
            $topic = Topic::find($topicTag->topic_id);            
            if ($topic) {
                $topicTag->topic_num = $topic->topic_num;
                $topicTag->save();
            }
        });
    }
}
