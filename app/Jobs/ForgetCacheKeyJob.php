<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class ForgetCacheKeyJob extends Job
{
    use InteractsWithQueue, Queueable, SerializesModels;

    private array $cacheKeys;
    public $delay;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $cacheKeys, $deleteTimestamp)
    {
        $this->queue = env('CACHE_QUEUE', 'cache-queue');
        $this->cacheKeys = $cacheKeys;
        $this->delay = $deleteTimestamp;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach($this->cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}
