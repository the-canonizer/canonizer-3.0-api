<?php

namespace App\Listeners;

use App\Events\IncreaseTopicViewCountEvent;
use App\Models\TopicView;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Hash;

class IncreaseTopicViewCountListener implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'camp-views-count';

    /**
     * Resolve the queue at dispatch time.
     *
     * Laravel reads $queue off an instance built without calling the constructor, so a
     * constructor assignment is silently ignored and the job lands on "default".
     * viaQueue() is the supported hook for a configurable queue name.
     *
     * The service ran QUEUE_CONNECTION=sync, so its $queue never mattered. The API runs
     * the database driver, so this must name a queue a worker actually consumes or the
     * jobs accumulate unread and view counts never move.
     */
    public function viaQueue(): string
    {
        return env('CAMP_VIEWS_QUEUE', 'camp-views-count');
    }

    /**
     * Handle the event.
     *
     * $event is deliberately untyped. Laravel 11 auto-discovers listeners in
     * app/Listeners by the handle() type-hint, which would register this listener a
     * second time on top of the explicit EventServiceProvider mapping and increment
     * every view twice. Every other listener in this app is untyped for the same
     * reason. Do not add the type-hint back without removing the explicit mapping.
     *
     * @param  IncreaseTopicViewCountEvent  $event
     * @return void
     */
    public function handle($event)
    {
        if (Hash::driver('argon2id')->check($event->asOfTime, '$argon2id$v=19$m=' . env('HASH_MEMORY_COST') . ',t=' . env('HASH_ITERATION') . ',p=' . env('HASH_PARALLELISM_FACTOR') . $event->view)) {
            if ($view = TopicView::where(['topic_num' => $event->topic_num, 'camp_num' => $event->camp_num])->whereBetween('created_at', [Carbon::now()->startOfDay()->timestamp, Carbon::now()->endOfDay()->timestamp])->first()) {
                $view->increment('views');
            } else {
                TopicView::create(['topic_num' => $event->topic_num, 'camp_num' => $event->camp_num, 'views' => 1]);
            }
        }
    }
}
