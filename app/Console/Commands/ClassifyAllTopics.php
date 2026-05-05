<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Topic;
use App\Models\TopicCategory;

class ClassifyAllTopics extends Command
{
    protected $signature = 'topics:classify-all
                            {--dry-run : Show what would be classified without writing}
                            {--limit=0 : Max topic_nums to process (0 = no limit)}';

    protected $description = 'Run AI classifier across all topics with NULL category_id and persist category_id. Idempotent.';

    public function handle(): int
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        if (!$apiKey) {
            $this->error('ANTHROPIC_API_KEY is not set');
            return 1;
        }

        $categories = TopicCategory::pluck('id', 'name')->toArray();
        if (empty($categories)) {
            $this->error('topic_categories table is empty. Run: php artisan db:seed --class=TopicCategorySeeder');
            return 1;
        }
        $catNames = array_keys($categories);

        $dryRun = (bool) $this->option('dry-run');
        $limit  = (int) $this->option('limit');

        $query = Topic::query()
            ->select('topic_num', DB::raw('MAX(topic_name) as topic_name'))
            ->whereNull('category_id')
            ->groupBy('topic_num');
        if ($limit > 0) $query->limit($limit);
        $topics = $query->get();

        $this->info('Topics needing classification: ' . $topics->count());
        if ($topics->isEmpty()) return 0;
        if ($dryRun) $this->warn('DRY RUN — no writes will be made');

        $okCount  = 0;
        $errCount = 0;
        $byCategory = [];

        foreach ($topics as $t) {
            $statement = $this->fetchAgreementStatement((int) $t->topic_num);
            $categoryName = $this->classify($t->topic_name, $statement, $catNames, $apiKey);

            if (!$categoryName || !isset($categories[$categoryName])) {
                $this->warn(sprintf('  [%d] %s — UNCLASSIFIED (model returned: %s)',
                    $t->topic_num, $t->topic_name, $categoryName ?? 'null'));
                $errCount++;
                continue;
            }

            $catId = $categories[$categoryName];
            $this->line(sprintf('  [%d] %s → %s', $t->topic_num, $t->topic_name, $categoryName));
            $byCategory[$categoryName] = ($byCategory[$categoryName] ?? 0) + 1;

            if (!$dryRun) {
                DB::table('topic')
                    ->where('topic_num', $t->topic_num)
                    ->whereNull('category_id')
                    ->update(['category_id' => $catId]);
            }
            $okCount++;
        }

        $this->newLine();
        $this->info(sprintf('Done — %d classified, %d failed%s', $okCount, $errCount, $dryRun ? ' (dry run)' : ''));
        if (!empty($byCategory)) {
            $this->info('Distribution:');
            foreach ($byCategory as $cat => $n) {
                $this->line(sprintf('  %-25s %d', $cat, $n));
            }
        }
        return $errCount > 0 ? 2 : 0;
    }

    private function fetchAgreementStatement(int $topicNum): string
    {
        $row = DB::table('statement')
            ->where('topic_num', $topicNum)
            ->where('camp_num', 1)
            ->whereNull('objector_nick_id')
            ->where('go_live_time', '<=', time())
            ->latest('submit_time')
            ->first();
        return $row->value ?? '';
    }

    private function classify(string $topicName, string $statement, array $catNames, string $apiKey): ?string
    {
        $list = '';
        foreach ($catNames as $i => $c) {
            $list .= ($i + 1) . ". {$c}\n";
        }

        $cleanStatement = trim(strip_tags((string) $statement));
        if (mb_strlen($cleanStatement) > 1500) {
            $cleanStatement = mb_substr($cleanStatement, 0, 1500);
        }

        $prompt = "You are classifying a Canonizer topic into exactly one category.\n\n"
                . "Topic: \"{$topicName}\"\n"
                . ($cleanStatement ? "Description: {$cleanStatement}\n" : '')
                . "\nCategories (pick the single best fit):\n{$list}\n"
                . "Respond with ONLY the category name, exactly as written above. No explanation, no punctuation, no quotes.";

        $payload = json_encode([
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 30,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT    => 30,
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($status !== 200 || !$body) {
            $this->warn(sprintf('    classifier upstream HTTP %d %s', $status, $err));
            return null;
        }

        $data = json_decode($body, true);
        $text = trim($data['content'][0]['text'] ?? '');
        if ($text === '') return null;

        foreach ($catNames as $c) {
            if (strcasecmp($c, $text) === 0) return $c;
        }
        return $text;
    }
}
