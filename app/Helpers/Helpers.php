<?php

namespace App\Helpers;

use App\Models\Camp;
use App\Models\Topic;

class Helpers
{
    public static function renderParentCampLinks($topic_num, $camp_num, $topic_name, $withLinks = false, $seprator = '>')
    {
        $camp = Camp::where([
            'camp_num' => $camp_num,
            'topic_num' => $topic_num,
            'grace_period' => 0,
            'objector_nick_id' => null,
        ])->orderBy('submit_time', 'desc')->first();

        if (!$camp) {
            return "";
        }

        if (is_null($camp->parent_camp_num)) {
            $topicLink = Topic::topicLink($topic_num, 1, $topic_name);
            return self::createLink($topic_name, $topicLink) ?? $camp->camp_name;
        }

        $campLink = Topic::topicLink($topic_num, $camp->camp_num, $topic_name, $camp->camp_name);

        return self::renderParentCampLinks($topic_num, $camp->parent_camp_num, $topic_name, $withLinks, $seprator) . ' ' . $seprator . ' ' . self::createLink($camp->camp_name, $campLink);
    }

    private static function createLink($text, $link)
    {
        return "<b><a href='" . $link . "'>" . $text . "</a></b>";
    }
}
