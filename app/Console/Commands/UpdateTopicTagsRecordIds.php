<?php

namespace App\Console\Commands;

use App\Models\Topic;
use App\Models\TopicTag;
use Illuminate\Console\Command;

class UpdateTopicTagsRecordIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'topic-tags:update-topic-record-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to update topic tags record ids';

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
        $this->withProgressBar(TopicTag::orderBy('topic_num', 'desc')->get(), function (TopicTag $topicTag) {
            $liveTopic = Topic::getLiveTopic($topicTag->topic_num, 'default');
            if ($liveTopic) {
                $topicTag->topic_id = $liveTopic->id;
                $topicTag->save();
            }
        });
    }
}
