<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Camp;
use App\Models\Thread;
use App\Models\Reply;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class UpdateMissingThreadAndPostTimestamps extends Command
{
    protected $signature = 'timestamps:update-threads-and-posts';
    protected $description = 'Update missing created_at and updated_at values in threads and posts';

    public function handle()
    {
        Log::info('Starting timestamp update process');
        $this->info('Timestamp update process started.');

        try {
            DB::beginTransaction();

            $this->updateThreadTimestamps();
            $this->updateReplyTimestamps();

            DB::commit();
            Log::info('Timestamp update process completed successfully');
            $this->info('Timestamp update process completed successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Timestamp update process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Timestamp update process failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function updateThreadTimestamps()
    {
        Log::info('Processing thread timestamps update');
        $this->info('Processing thread timestamps...');

        try {
            $threads = Thread::where(function ($query) {
                $query->where('created_at', 0)
                    ->orWhereNull('created_at');
            })
                ->orWhere(function ($query) {
                    $query->where('updated_at', 0)
                        ->orWhereNull('updated_at');
                })
                ->with(['replies'])
                ->get();

            $this->info("Found {$threads->count()} threads with missing timestamps");

            foreach ($threads as $thread) {
                $this->processThread($thread);
            }

            Log::info('Thread timestamps update completed');
            $this->info('Thread timestamps update completed.');
        } catch (Exception $e) {
            Log::error('Error updating thread timestamps', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Error updating thread timestamps: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processThread(Thread $thread)
    {
        $originalData = $thread->toArray();
        $updated = false;
        $reason = null;

        try {
            // Check replies for valid timestamps
            $validReply = $thread->replies
                ->filter(function ($reply) {
                    return $reply->created_at && $reply->created_at != 0;
                })
                ->sortBy('created_at')
                ->first();

            if ($validReply) {
                $thread->created_at = $validReply->created_at;
                $thread->updated_at = $validReply->updated_at ?? $validReply->created_at;
                $updated = true;
                $reason = 'updated from reply timestamps';
            } else {
                // Check other threads in same camp
                $otherThread = Thread::where('camp_id', $thread->camp_id)
                    ->where('id', '!=', $thread->id)
                    ->where(function ($query) {
                        $query->whereNotNull('created_at')
                            ->where('created_at', '!=', 0);
                    })
                    ->orderBy('created_at')
                    ->first();

                if ($otherThread) {
                    $thread->created_at = $otherThread->created_at;
                    $thread->updated_at = $otherThread->updated_at;
                    $updated = true;
                    $reason = 'updated from other thread in same camp';
                } else {
                    // Fall back to camp time
                    $camp = Camp::where('camp_num', 1)
                        ->where('topic_num', $thread->topic_id)
                        ->first();

                    if ($camp && $camp->submit_time) {
                        $thread->created_at = $camp->submit_time;
                        $thread->updated_at = $camp->submit_time;
                        $updated = true;
                        $reason = 'updated from camp submit time';
                    }
                }
            }

            if ($updated) {
                $thread->save();
                Log::info("Updated thread #{$thread->id}", [
                    'thread_id' => $thread->id,
                    'reason' => $reason,
                    'new_timestamps' => [
                        'created_at' => $thread->created_at,
                        'updated_at' => $thread->updated_at
                    ]
                ]);
                $this->info("Updated thread #{$thread->id} - {$reason}");
            } else {
                Log::warning("Could not update thread #{$thread->id} - no timestamp source found");
                $this->warn("Could not update thread #{$thread->id} - no timestamp source found");
            }
        } catch (Exception $e) {
            Log::error("Error processing thread #{$thread->id}: " . $e->getMessage());
            $this->error("Error processing thread #{$thread->id}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function updateReplyTimestamps()
    {
        Log::info('Processing reply timestamps update');
        $this->info('Processing reply timestamps...');

        try {
            $replies = Reply::where(function ($query) {
                $query->where('created_at', 0)
                    ->orWhereNull('created_at');
            })
                ->orWhere(function ($query) {
                    $query->where('updated_at', 0)
                        ->orWhereNull('updated_at');
                })
                ->get();

            $this->info("Found {$replies->count()} replies with missing timestamps");

            foreach ($replies as $reply) {
                $this->processReply($reply);
            }

            Log::info('Reply timestamps update completed');
            $this->info('Reply timestamps update completed.');
        } catch (Exception $e) {
            Log::error('Error updating reply timestamps: ' . $e->getMessage());
            $this->error('Error updating reply timestamps: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processReply(Reply $reply)
    {
        $originalData = $reply->toArray();
        $updated = false;
        $reason = null;

        try {
            // Check other replies in same thread
            $validReply = Reply::where('c_thread_id', $reply->thread_id)
                ->where('id', '!=', $reply->id)
                ->where(function ($query) {
                    $query->whereNotNull('created_at')
                        ->where('created_at', '!=', 0);
                })
                ->orderBy('created_at')
                ->first();

            if ($validReply) {
                $reply->created_at = $validReply->created_at;
                $reply->updated_at = $validReply->updated_at ?? $validReply->created_at;
                $updated = true;
                $reason = 'updated from other reply timestamps';
            } else {
                // Fall back to thread timestamps
                $thread = Thread::find($reply->thread_id);
                if ($thread && $thread->created_at) {
                    $reply->created_at = $thread->created_at;
                    $reply->updated_at = $thread->updated_at ?? $thread->created_at;
                    $updated = true;
                    $reason = 'updated from thread timestamps';
                } elseif ($thread) {
                    // Fall back to camp time
                    $camp = Camp::where('camp_num', 1)
                        ->where('topic_num', $thread->topic_id)
                        ->first();

                    if ($camp && $camp->submit_time) {
                        $reply->created_at = $camp->submit_time;
                        $reply->updated_at = $camp->submit_time;
                        $updated = true;
                        $reason = 'updated from camp submit time';
                    }
                }
            }

            if ($updated) {
                $reply->save();
                Log::info("Updated reply #{$reply->id}", [
                    'reply_id' => $reply->id,
                    'reason' => $reason,
                    'new_timestamps' => [
                        'created_at' => $reply->created_at,
                        'updated_at' => $reply->updated_at
                    ]
                ]);
                $this->info("Updated reply #{$reply->id} - {$reason}");
            } else {
                Log::warning("Could not update reply #{$reply->id} - no timestamp source found");
                $this->warn("Could not update reply #{$reply->id} - no timestamp source found");
            }
        } catch (Exception $e) {
            Log::error("Error processing reply #{$reply->id}: " . $e->getMessage());
            $this->error("Error processing reply #{$reply->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
