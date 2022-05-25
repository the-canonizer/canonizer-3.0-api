<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Camp;
use App\Models\ActivityUser;

class ActivityLoggerJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    private $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $activityLog = activity($this->data['log_type'])
            ->performedOn($this->data['model'])
            ->causedBy($this->data['user'])
            ->withProperties(['topic_num' => $this->data['topic_num'], 'camp_num' => $this->data['camp_num'], 'url' => $this->data['url']])
            ->log($this->data['activity'] . ' by ' . $this->data['user']->getUserFullName());

        if (isset($activityLog) && $activityLog->id) {
            $users = [];
            $subscribers = Camp::getCampSubscribers($this->data['topic_num'], $this->data['camp_num']);
            $supporters = Camp::getDirectCampSupporterIds($this->data['topic_num'], $this->data['camp_num']);
            $users = array_unique(array_merge($subscribers, $supporters));

            foreach ($users as $user) {
                $activityUser = new ActivityUser();
                $activityUser->activity_id = $activityLog->id;
                $activityUser->user_id = $user;
                $activityUser->save();
            }
        }
    }
}
