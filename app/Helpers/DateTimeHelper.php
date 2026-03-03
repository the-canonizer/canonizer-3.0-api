<?php

namespace App\Helpers;

class DateTimeHelper
{
    /**
     * get asOfTime.
     *
     * @param int $asOfTime
     * @return int $asOfDate
     */
    public function getAsOfDate($asOfTime)
    {
        $asOfDate = date('Y-m-d');

        if (isset($asOfTime)) {
            $asOfDate =  $asOfTime;
        }
        $asOfDate = strtotime(date('Y-m-d', $asOfDate));

        return $asOfDate;
    }
}
