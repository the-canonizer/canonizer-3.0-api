<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\{Camp, Statement, Topic, TopicView};
use Illuminate\Support\Facades\DB;

class Helpers
{
    public static function renderParentCampLinks($topic_num, $camp_num, $topic_name, $withLinks = false, $change_type = null, $iteration = 0) // Always place $iteration as the last parameter
    {
        $seprator = '<img src="' . env('APP_URL') . '/assets/images/seprator.png" alt="seprator" />';
        $filter['topicNum'] = $topic_num;
        $filter['campNum'] = $camp_num ?? 1;
        $camp = Camp::getLiveCamp($filter);

        if (!$camp) {
            return "";
        }

        if (is_null($camp->parent_camp_num)) {
            $topicLink = Topic::topicLink($topic_num, 1, $topic_name);
            return self::createLink($topic_name . ($change_type === 'camp' && $camp_num === 1 && $iteration == 0 ? ' ' . $seprator . ' ' . $camp->camp_name : ''), $topicLink) ?? $camp->camp_name;
        }

        $campLink = Topic::topicLink($topic_num, $camp->camp_num, $topic_name, $camp->camp_name);

        return self::renderParentCampLinks($topic_num, $camp->parent_camp_num, $topic_name, $withLinks, $change_type, ++$iteration) . ' ' . $seprator . ' ' . self::createLink($camp->camp_name, $campLink);
    }

    private static function createLink($text, $link)
    {
        return "<b><a href='" . $link . "'>" . $text . "</a></b>";
    }

    public static function getChangesCount($model, $topic_num, $camp_num)
    {

        $where = [
            ['topic_num', '=', $topic_num],
            ['go_live_time', '>', Carbon::now()->timestamp],
            ['submit_time', '<=', Carbon::now()->timestamp],
            ['objector_nick_id', '=', NULL],
            ['grace_period', '=', 0],
        ];

        if (!($model instanceof Topic)) {
            $where[] = ['camp_num', '=', $camp_num];
        }
        if ($model instanceof Statement) {
            $where[] = ['is_draft', '=', 0];
        }

        return $model::where('topic_num', $topic_num)
            ->where($where)
            ->count();
    }

    public static function getCampViewsByDate(int $topic_num, int $camp_num = 1, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        return TopicView::where('topic_num', $topic_num)
            ->when($camp_num > 1, fn ($query) => $query->where('camp_num', $camp_num))
            ->when(
                $startDate && $endDate,
                fn ($query) => $query->whereBetween('created_at', [$startDate->startOfDay()->timestamp, $endDate->endOfDay()->timestamp]),
                fn ($query) => $query->when(
                    $endDate,
                    fn ($query) => $query->where('created_at', '<=', $endDate->endOfDay()->timestamp),
                    fn ($query) => $query->when(
                        $startDate,
                        fn ($query) => $query->where('created_at', '>=', $startDate->startOfDay()->timestamp),
                    )
                )
            )->sum('views');
    }

    public static function stripTagsExcept($html, $excludeTags = [])
    {
        if (!is_string($html)) {
            return $html;
        }
        $excludeTagsPattern = implode('|', array_map(
            function ($tag) {
                return preg_quote($tag, '/');
            },
            $excludeTags
        ));

        // Remove the content and tags of the excluded tags
        $pattern = '/<(' . $excludeTagsPattern . ')\b[^>]*>(.*?)<\/\1>/is';
        $html = preg_replace($pattern, '', $html);

        // Strip all remaining tags
        $cleanedText = strip_tags($html);

        // Decode HTML entities to get the proper text
        // $cleanedText = html_entity_decode($cleanedText, ENT_QUOTES, 'UTF-8');

        // Trim to the specified limit
        return $cleanedText;
    }

    public static function getHistoryCountsByChange($liveRecord, $filter) {

        if (($liveRecord instanceof Topic)) {
            $baseQuery = Topic::where('topic_num', $filter['topicNum'])->latest('submit_time');
        } else if ($liveRecord instanceof Camp) {
            $baseQuery = Camp::where('topic_num', $filter['topicNum'])->where('camp_num', '=', $filter['campNum'])->latest('submit_time');
        } else if ($liveRecord instanceof Statement) {
            $baseQuery = Statement::where('topic_num', $filter['topicNum'])->where('camp_num', $filter['campNum'])
                                ->where('is_draft', 0)
                                ->latest('submit_time');
        }

        // Current timestamp for consistent comparison
        $currentTime = time();

        $counts = $baseQuery->select(
            DB::raw('COUNT(*) as total_changes'),
            DB::raw('SUM(CASE WHEN id = ' . $liveRecord->id . ' THEN 1 ELSE 0 END) as live_changes'),
            DB::raw('SUM(CASE WHEN objector_nick_id IS NOT NULL THEN 1 ELSE 0 END) as objected_changes'),
            DB::raw('SUM(CASE WHEN go_live_time > ' . $currentTime . ' AND objector_nick_id IS NULL AND submit_time <= ' . $currentTime . ' THEN 1 ELSE 0 END) as in_review_changes'),
            DB::raw('SUM(CASE WHEN go_live_time <= ' . $currentTime . ' AND objector_nick_id IS NULL AND id != ' . $liveRecord->id . ' AND submit_time <= ' . $currentTime . ' THEN 1 ELSE 0 END) as old_changes')
        )
        ->first();

        $historyCounts = [
            'total_changes' => (int) $counts->total_changes,
            'live_changes' => (int) $counts->live_changes,
            'objected_changes' => (int) $counts->objected_changes,
            'in_review_changes' => (int) $counts->in_review_changes,
            'old_changes' => (int) $counts->old_changes,
        ];

        return $historyCounts;
    }
}
