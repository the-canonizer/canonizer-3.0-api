<?php

namespace App\Helpers;

use App\Models\{Camp, Statement, Topic};
use Carbon\Carbon;

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

        return $model::where('topic_num', $topic_num)
            ->where($where)
            ->count();
    }

    public static function updateTopicsInReview($topic)
    {
        $inReviewTopicChanges = Topic::where([
            ['topic_num', '=', $topic->topic_num],
            ['submit_time', '<', $topic->submit_time],
            ['go_live_time', '>', Carbon::now()->timestamp],
            ['grace_period', '=', 0]
        ])->whereNull('objector_nick_id')->get();
        if (count($inReviewTopicChanges)) {
            foreach ($inReviewTopicChanges as $key => $topic) {
                Topic::where('id', $topic->id)->update(['go_live_time' => strtotime(date('Y-m-d H:i:s')) - ($key + 1)]);
            }
        }
    }

    public static function updateStatementsInReview($statement)
    {
        $inReviewStatementChanges = Statement::where([
            ['topic_num', '=', $statement->topic_num],
            ['camp_num', '=', $statement->camp_num],
            ['submit_time', '<', $statement->submit_time],
            ['go_live_time', '>', Carbon::now()->timestamp],
            ['grace_period', '=', 0]
        ])->whereNull('objector_nick_id')->get();
        if (count($inReviewStatementChanges)) {
            foreach ($inReviewStatementChanges as $key => $statement) {
                Statement::where('id', $statement->id)->update(['go_live_time' => strtotime(date('Y-m-d H:i:s')) - ($key + 1)]);
            }
        }
    }

    public static function updateCampsInReview($camp)
    {
        $inReviewCampChanges = Camp::where([
            ['topic_num', '=', $camp->topic_num],
            ['camp_num', '=', $camp->camp_num],
            ['submit_time', '<', $camp->submit_time],
            ['go_live_time', '>', Carbon::now()->timestamp],
            ['grace_period', '=', 0]
        ])->whereNull('objector_nick_id')->orderBy('go_live_time', 'desc')->get();
        if (count($inReviewCampChanges)) {
            foreach ($inReviewCampChanges as $key => $Camp) {
                Camp::where('id', $Camp->id)->update(['go_live_time' => strtotime(date('Y-m-d H:i:s')) - ($key + 1)]);
            }
        }
    }
}
