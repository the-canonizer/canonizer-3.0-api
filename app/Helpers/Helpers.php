<?php

namespace App\Helpers;

use App\Models\Camp;
use App\Models\Topic;
use Carbon\Carbon;

class Helpers
{
    public static function renderParentCampLinks($topic_num, $camp_num, $topic_name, $withLinks = false)
    {
        $seprator = '<svg style="width: 15px; color: #3869d4; height: 12px;" viewBox="64 64 896 896" focusable="false" data-icon="double-right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M533.2 492.3L277.9 166.1c-3-3.9-7.7-6.1-12.6-6.1H188c-6.7 0-10.4 7.7-6.3 12.9L447.1 512 181.7 851.1A7.98 7.98 0 00188 864h77.3c4.9 0 9.6-2.3 12.6-6.1l255.3-326.1c9.1-11.7 9.1-27.9 0-39.5zm304 0L581.9 166.1c-3-3.9-7.7-6.1-12.6-6.1H492c-6.7 0-10.4 7.7-6.3 12.9L751.1 512 485.7 851.1A7.98 7.98 0 00492 864h77.3c4.9 0 9.6-2.3 12.6-6.1l255.3-326.1c9.1-11.7 9.1-27.9 0-39.5z"></path></svg>';
        $filter['topicNum'] = $topic_num;
        $filter['campNum'] = $camp_num ?? 1;
        $camp = Camp::getLiveCamp($filter);

        if (!$camp) {
            return "";
        }

        if (is_null($camp->parent_camp_num)) {
            $topicLink = Topic::topicLink($topic_num, 1, $topic_name);
            return self::createLink($topic_name, $topicLink) ?? $camp->camp_name;
        }

        $campLink = Topic::topicLink($topic_num, $camp->camp_num, $topic_name, $camp->camp_name);

        return self::renderParentCampLinks($topic_num, $camp->parent_camp_num, $topic_name, $withLinks) . ' ' . $seprator . ' ' . self::createLink($camp->camp_name, $campLink);
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
}
