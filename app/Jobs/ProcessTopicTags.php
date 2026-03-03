<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Tag;
use App\Models\Camp;
use App\Models\Topic;
use App\Models\TopicTag;
use App\Models\Statement;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Models\TopicTagFailure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessTopicTags implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $topicsWithoutTags;
    public $enableLogging = false;
    public $delay;

    public function __construct($topicsWithoutTags)
    {
        $this->queue = env('TAG_TOPIC_QUEUE', 'tag-topic-queue');
        $this->topicsWithoutTags = $topicsWithoutTags;
        $this->delay = Carbon::now()->addSeconds(5);
    }

    public function handle()
    {
        try {
            $dataForApi = [];

            foreach ($this->topicsWithoutTags as $topic) {

                $topic = Topic::where('topic_num', $topic->topic_num)
                    ->whereHas('nameSpace', function ($query) {
                        $query->where('name', 'General');
                    })
                    ->whereNull('objector_nick_id')
                    ->where('go_live_time', '<=', time())
                    ->latest('submit_time')->first();

                if (!isset($topic) || empty($topic->topic_num)) {
                    if ($this->enableLogging) {
                        Log::warning("processTopic ==> Skipping invalid topic: " . json_encode($topic));
                    }
                    $this->logFailure($topic, "No live topic found");
                    continue;
                }
                if ($this->enableLogging) {
                    Log::info("processTopic ==> Processing topic: {$topic->topic_name} - {$topic->topic_num}");
                }

                $liveCamp = Camp::where('topic_num', $topic->topic_num)
                    ->where('camp_num', '=', 1)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', time())
                    ->latest('go_live_time')->first();

                if (!$liveCamp) {
                    if ($this->enableLogging) {
                        Log::warning("processTopic ==> No live camp found for topic: {$topic->topic_name} - {$topic->topic_num}");
                    }

                    $this->logFailure($topic, "No live camp found");
                    continue;
                }

                $liveStatement = Statement::where('topic_num', $topic->topic_num)
                    ->where('camp_num', $liveCamp->camp_num)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', time())
                    ->where('is_draft', 0)
                    ->orderBy('submit_time', 'desc')
                    ->first();

                if (!$liveStatement) {
                    if ($this->enableLogging) {
                        Log::warning("processTopic ==> No live statement found for topic: {$topic->topic_name} - {$topic->topic_num}");
                    }

                    $this->logFailure($topic, "No live statement found");
                    continue;
                }

                $noOfWordsAllowed = 250;
                $dataForApi[] = [
                    'topic' => $topic->topic_name,
                    'description' => Str::words($liveStatement->value, $noOfWordsAllowed),
                    'topic_num' => $topic->topic_num,
                ];

                if ($this->enableLogging) {
                    Log::info("processTopic ==> Added topic: {$topic->topic_name} with statement value to dataForApi.");
                }
            }

            if (empty($dataForApi)) {
                Log::info("processTopic ==> No data to send to the API. Exiting.");
                return;
            }

            $url = env('API_OPEN_AI');
            if ($this->enableLogging) {
                Log::info("processTopic ==> Making API call to URL: '{$url}'");
            }

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $dataForApi);

            Log::info("processTopic ==> API response: " . json_encode($response->json()));

            if ($response->successful()) {
                if ($this->enableLogging) {
                    Log::info("processTopic ==> API call successful.");
                }
                $this->handleApiResponse($response->json(), $this->topicsWithoutTags);
            } else {
                if ($this->enableLogging) {
                    Log::error("processTopic ==> API call failed: " . $response->json());
                }
            }
        } catch (\Exception $e) {
            Log::error("processTopic ==> Error processing topics: " . $e->getMessage());
        }
    }

    private function handleApiResponse(array $tagsFromApi, $topicsWithoutTags)
    {
        if ($this->enableLogging) {
            Log::info("handleApiResponse ==> Processing API response");
        }

        foreach ($tagsFromApi as $tagData) {
            $topicName = $tagData['topic'];
            $tagName = $tagData['tag'];
            $topic_num = $tagData['topic_num'];

            if ($this->enableLogging) {
                Log::info("handleApiResponse ==> Processing tag '{$tagName}' for topic '{$topicName}'");
            }

            $tag = Tag::where('title', $tagName)->first();
            $topic = Topic::where('topic_num', $topic_num)
                ->whereHas('nameSpace', function ($query) {
                    $query->where('name', 'General');
                })
                ->whereNull('objector_nick_id')
                ->where('go_live_time', '<=', time())
                ->latest('submit_time')->first();

            if (!$tag) {
                if ($this->enableLogging) {
                    Log::warning("handleApiResponse ==> Tag '{$tagName}' not found in the database.");
                }
                $this->logFailure($topic, "Tag '{$tagName}' not found in the database after API response");
                continue;
            }
            if (!$topic) {
                if ($this->enableLogging) {
                    Log::warning("handleApiResponse ==> Topic '{$topicName}' not found in the database.");
                }
                $this->logFailure($topic, "Topic '{$topicName}' not found in the database after API response");

                continue;
            }

            TopicTag::updateOrCreate(
                ['topic_num' => $topic->topic_num, 'tag_id' => $tag->id],
                ['topic_num' => $topic->topic_num, 'tag_id' => $tag->id]
            );
            if ($this->enableLogging) {
                Log::info("handleApiResponse ==> Associated tag '{$tagName}' with topic '{$topicName}'");
            }
        }
        if ($this->enableLogging) {
            Log::info("handleApiResponse ==> Finished processing API response.");
        }
    }

    private function logFailure($topic, $reason)
    {
        TopicTagFailure::updateOrCreate([
            'topic_num' => $topic->topic_num,
            'fail_to_associate_tag_reason' => $reason,
        ]);
    }
}
