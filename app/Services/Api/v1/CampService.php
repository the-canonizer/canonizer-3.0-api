<?php

namespace App\Services\Api\v1;

use App\Models\Algorithm;
use App\Models\Camp;
use App\Models\Nickname;
use App\Models\Support;
use App\Models\Topic;
use App\Models\TopicSupport;
use App\Models\CampSubscription;
use App\Models\TopicTag;
use App\Services\Api\v1\AlgorithmService;
use App\Services\Api\v1\TopicService;
use App\Helpers\DateTimeHelper;
use App\Helpers\UtilHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CampService
{
    public $traversetempArray = [];
    public $sessionTempArray = [];

    public function getLiveCamp($topicNumber, $campNumber, $filter = array(), $asOfTime = null, $asOf = 'default')
    {
        $asOfTime = $asOfTime ?? time();
        $camp = Camp::where('topic_num', $topicNumber)
            ->where('camp_num', $campNumber)
            ->where('objector_nick_id', NULL);

        if ($asOf == 'default') {
            $camp->where('go_live_time', '<=', time());
        } elseif ($asOf == 'bydate') {
            $camp->where('go_live_time', '<=', $asOfTime);
        }

        $liveCamp = $camp->orderBy('go_live_time', 'desc')->first();

        return $liveCamp;
    }

    public static function getCampCreatedDate($campNumber, $topicNumber)
    {
        return Camp::where('topic_num', $topicNumber)
            ->where('camp_num', $campNumber)
            ->pluck('submit_time')
            ->first();
    }

    public function campChildrens($topicNumber, $parentCamp)
    {
        try {
            $childs = DB::table('camp')
                ->where('topic_num', $topicNumber)
                ->where('parent_camp_num', $parentCamp)
                ->where('camp_name', '!=', 'Agreement')
                ->where('objector_nick_id', '=', null)
                ->where('grace_period', '=', 0)
                ->where('go_live_time', '<=', time())
                ->groupBy('camp_num')
                ->get();

            return $childs;
        } catch (\Exception $th) {
            abort(401, "Get Camp Childrens Exception: " . $th->getMessage());
        }
    }

    public function getCamptSupportCount($algorithm, $topicNumber, $campNumber, $asOfTime, $nickNameId = null, $full_score = false)
    {
        $asOfTime = $asOfTime ?? time();
        $algo = $algorithm;
        $key = "score_tree_{$topicNumber}_{$algo}";
        
        if (!Arr::exists($this->sessionTempArray, $key)) {
            $expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree($algo, $topicNumber, $asOfTime);
            $this->sessionTempArray[$key] = $expertCampReducedTree;
        } else {
            $expertCampReducedTree = $this->sessionTempArray[$key];
        }

        $total_score = 0;
        if (array_key_exists('camp_wise_tree', $expertCampReducedTree) && array_key_exists($campNumber, $expertCampReducedTree['camp_wise_tree'])) {
            foreach ($expertCampReducedTree['camp_wise_tree'][$campNumber] as $tree_node) {
                if (count($tree_node) > 0) {
                    foreach ($tree_node as $score) {
                        $total_score += $full_score ? $score['full_score'] : $score['score'];
                    }
                }
            }
        }

        return $total_score;
    }

    public function prepareCampTree($algorithm, $topicNumber, $asOfTime, $startCamp = 1, $rootUrl = '', $nickNameId = null, $asOf = 'default', $fetchTopicHistory = 0)
    {
        try {
            Log::info("prepareCampTree: Start algo={$algorithm} topic={$topicNumber}");
            $this->traversetempArray = [];
            $this->sessionTempArray = [];

            $topic = (new TopicService())->getLiveTopic($topicNumber, $asOfTime, ['nofilter' => true], $asOf, $fetchTopicHistory);
            Log::info("prepareCampTree: getLiveTopic done");
            $topicName = (isset($topic) && isset($topic->topic_name)) ? $topic->topic_name : '';
            $agreementCamp = $this->getLiveCamp($topicNumber, 1, ['nofilter' => true], $asOfTime, $asOf);
            Log::info("prepareCampTree: getLiveCamp done");

            $tree = [];
            $level = 1;
            $tree[$startCamp]['topic_id'] = $topicNumber;
            $tree[$startCamp]['level'] = $level;
            $tree[$startCamp]['camp_id'] = $startCamp;
            $tree[$startCamp]['camp_name'] = (isset($agreementCamp) && isset($agreementCamp->camp_name)) ? $agreementCamp->camp_name : '';
            $tree[$startCamp]['title'] = $topicName;
            $tree[$startCamp]['score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime, $nickNameId);
            $tree[$startCamp]['full_score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime, $nickNameId, true);
            Log::info("prepareCampTree: getCamptSupportCount done");
            $tree[$startCamp]['submitter_nick_id'] = $topic->submitter_nick_id ?? '';
            $tree[$startCamp]['children'] = $this->traverseCampTree($algorithm, $topicNumber, $startCamp, $rootUrl, $tree, $level, null, $asOfTime, $asOf);
            Log::info("prepareCampTree: traverseCampTree done");

            $result = TopicSupport::sumTranversedArraySupportCountP($tree);
            Log::info("prepareCampTree: sumTranversedArraySupportCountP done");
            return $result;
        } catch (\Exception $th) {
            Log::error("Prepare Camp Tree Exception: " . $th->getMessage() . " at " . $th->getFile() . ":" . $th->getLine());
            throw new \Exception("Prepare Camp Tree Exception: " . $th->getMessage());
        }
    }

    public function traverseCampTree($algorithm, $topicNumber, $parentCamp, $rootUrl, &$lastArray, $level, $lastparent = null, $asOfTime = null, $asOf = 'default')
    {
        try {
            $key = $topicNumber . '-' . $parentCamp . '-' . $lastparent;
            if (in_array($key, $this->traversetempArray)) {
                return [];
            }
            $this->traversetempArray[] = $key;
            $childs = $this->campChildrens($topicNumber, $parentCamp);

            $array = [];
            $level++;
            foreach ($childs as $child) {
                $array[$child->camp_num]['topic_id'] = $topicNumber;
                $array[$child->camp_num]['level'] = $level;
                $array[$child->camp_num]['camp_id'] = $child->camp_num;
                $array[$child->camp_num]['camp_name'] = $child->camp_name;
                $array[$child->camp_num]['title'] = $child->camp_name;
                $array[$child->camp_num]['score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime);
                $array[$child->camp_num]['full_score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime, null, true);
                $array[$child->camp_num]['submitter_nick_id'] = $child->submitter_nick_id ?? '';
                $children = $this->traverseCampTree($algorithm, $child->topic_num, $child->camp_num, $rootUrl, $array, $level, $child->parent_camp_num, $asOfTime, $asOf);
                $array[$child->camp_num]['children'] = is_array($children) ? $children : [];
            }
            return $array;
        } catch (\Exception $th) {
            abort(401, "Traverse Camp Tree Exception: " . $th->getMessage());
        }
    }

    public function getCampAndNickNameWiseSupportTree($algorithm, $topicNumber, $asOfTime)
    {
        try {
            $is_add_reminder_back_flag = 1;
            $nick_name_support_tree = [];
            $nick_name_wise_support = [];
            $camp_wise_score = [];

            $topic_support = Support::where('topic_num', '=', $topicNumber)
                ->where('delegate_nick_name_id', 0)
                ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                ->orderBy('camp_num', 'ASC')->orderBy('support_order', 'ASC')
                ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
                ->get();

            if (count($topic_support) > 0) {
                foreach ($topic_support as $support) {
                    if (array_key_exists($support->nick_name_id, $nick_name_wise_support)) {
                        array_push($nick_name_wise_support[$support->nick_name_id], $support);
                    } else {
                        $nick_name_wise_support[$support->nick_name_id] = [$support];
                    }
                }
            }

            foreach ($nick_name_wise_support as $nickNameId => $support_camp) {
                $multiSupport = count($support_camp) > 1 ? 1 : 0;
                $algorithmService = new AlgorithmService();
                foreach ($support_camp as $support) {
                    $supportPoint = $algorithmService->{$algorithm}($support->nick_name_id, $support->topic_num, $support->camp_num, $asOfTime);
                    $support_total = $multiSupport ? round($supportPoint / (2 ** ($support->support_order)), 3) : $supportPoint;
                    $full_support_total = $supportPoint;

                    $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['score'] = $support_total;
                    $camp_wise_score[$support->camp_num][$support->support_order][$support->nick_name_id]['score'] = $support_total;
                    $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['full_score'] = $full_support_total;
                    $camp_wise_score[$support->camp_num][$support->support_order][$support->nick_name_id]['full_score'] = $full_support_total;
                }
            }

            if (count($nick_name_support_tree) > 0) {
                foreach ($nick_name_support_tree as $nickNameId => $scoreData) {
                    ksort($scoreData);
                    $index = 0;
                    $multiSupport = count($scoreData) > 1 ? 1 : 0;
                    foreach ($scoreData as $support_order => $camp_score) {
                        $index++;
                        foreach ($camp_score as $campNum => $score) {
                            if ($support_order > 1 && $index == count($scoreData) && $is_add_reminder_back_flag) {
                                if (array_key_exists($nickNameId, $nick_name_support_tree) && array_key_exists(1, $nick_name_support_tree[$nickNameId]) && count(array_keys($nick_name_support_tree[$nickNameId][1])) > 0) {
                                    $campNumber = array_keys($nick_name_support_tree[$nickNameId][1])[0];
                                    $nick_name_support_tree[$nickNameId][1][$campNumber]['score'] += $score['score'];
                                    $camp_wise_score[$campNumber][1][$nickNameId]['score'] += $score['score'];
                                    $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber, $campNumber, $nickNameId, $support_order, $camp_wise_score[$campNumber][1][$nickNameId]['score'], $multiSupport, [], $asOfTime);
                                    $nick_name_support_tree[$nickNameId][1][$campNumber]['delegates'] = $delegateTree;
                                }
                            }
                            $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber, $campNum, $nickNameId, $support_order, $nick_name_support_tree[$nickNameId][$support_order][$campNum]['score'], $multiSupport, [], $asOfTime);
                            $nick_name_support_tree[$nickNameId][$support_order][$campNum]['delegates'] = $delegateTree;
                        }
                    }
                }
            }

            return ['camp_wise_tree' => $camp_wise_score, 'nick_name_wise_tree' => $nick_name_support_tree];
        } catch (\Exception $th) {
            throw new \Exception("Get Camp and NickName Wise Support Tree Exception: " . $th->getMessage());
        }
    }

    public function delegateSupportTree($algorithm, $topicNumber, $campnum, $delegateNickId, $parent_support_order, $parent_score, $multiSupport, $array = [], $asOfTime = null)
    {
        try {
            $nick_name_support_tree = [];
            $nick_name_wise_support = [];
            $nick_name_delegate_support_tree = [];
            $is_add_reminder_back_flag = 1;

            if (!Arr::exists($this->sessionTempArray, "topic-support-{$topicNumber}")) {
                $supportData = Support::where('topic_num', '=', $topicNumber)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start', 'DESC')
                    ->select(['support_order', 'camp_num', 'nick_name_id', 'delegate_nick_name_id', 'topic_num'])
                    ->get();
                $this->sessionTempArray["topic-support-{$topicNumber}"] = $supportData;
            }

            $delegatedSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(fn($item) => $item->delegate_nick_name_id == $delegateNickId);

            if (count($delegatedSupports) > 0) {
                foreach ($delegatedSupports as $support) {
                    if (array_key_exists($support->nick_name_id, $nick_name_wise_support)) {
                        array_push($nick_name_wise_support[$support->nick_name_id], $support);
                    } else {
                        $nick_name_wise_support[$support->nick_name_id] = [$support];
                    }
                }
            }

            $algorithmService = new AlgorithmService();
            foreach ($nick_name_wise_support as $nickNameId => $support_camp) {
                foreach ($support_camp as $support) {
                    $supportPoint = $algorithmService->{$algorithm}($support->nick_name_id, $support->topic_num, $support->camp_num, $asOfTime);
                    $support_total = $multiSupport ? round($supportPoint / (2 ** ($support->support_order)), 3) : $supportPoint;
                    $full_support_total = $supportPoint;
                    $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['score'] = $support_total;
                    $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['full_score'] = $full_support_total;
                }
            }

            if (count($nick_name_support_tree) > 0) {
                foreach ($nick_name_support_tree as $nickNameId => $scoreData) {
                    ksort($scoreData);
                    $index = 0;
                    $multiSupport = count($scoreData) > 1 ? 1 : 0;
                    foreach ($scoreData as $support_order => $camp_score) {
                        $index++;
                        foreach ($camp_score as $campNum => $score) {
                            if ($support_order > 1 && $index == count($scoreData) && $is_add_reminder_back_flag) {
                                if (array_key_exists($nickNameId, $nick_name_support_tree) && array_key_exists(1, $nick_name_support_tree[$nickNameId]) && count(array_keys($nick_name_support_tree[$nickNameId][1])) > 0) {
                                    $campNumber = array_keys($nick_name_support_tree[$nickNameId][1])[0];
                                    $nick_name_support_tree[$nickNameId][1][$campNumber]['score'] += $score['score'];
                                    $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber, $campNumber, $nickNameId, $parent_support_order, $parent_score, $multiSupport, [], $asOfTime);
                                    $nick_name_support_tree[$nickNameId][1][$campNumber]['delegates'] = $delegateTree;
                                }
                            }
                            $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber, $campNum, $nickNameId, $parent_support_order, $parent_score, $multiSupport, [], $asOfTime);
                            $nick_name_support_tree[$nickNameId][$support_order][$campNum]['delegates'] = $delegateTree;
                        }
                    }
                }
            }

            if (count($nick_name_support_tree) > 0) {
                foreach ($nick_name_support_tree as $nick => $data) {
                    foreach ($data as $support_order => $camp_score) {
                        foreach ($camp_score as $campNum => $score) {
                            if ($campNum == $campnum) {
                                $nick_name_delegate_support_tree[$nick]['score'] = $score['score'];
                                $nick_name_delegate_support_tree[$nick]['full_score'] = $score['full_score'];
                                $nick_name_delegate_support_tree[$nick]['delegates'] = $score['delegates'];
                            }
                        }
                    }
                }
            }
            return $nick_name_delegate_support_tree;
        } catch (\Exception $th) {
            throw new \Exception("Delegate Support Tree Exception: " . $th->getMessage());
        }
    }

    public function prepareCampTimeline($algorithm, $topicNumber, $asOfTime, $startCamp = 1, $rootUrl = '', $nickNameId = null, $asOf = 'default', $fetchTopicHistory = 0)
    {
        try {
            $this->traversetempArray = [];
            $this->sessionTempArray = [];

            $topic = (new TopicService())->getLiveTopic($topicNumber, $asOfTime, ['nofilter' => true], $asOf, $fetchTopicHistory);
            $topicName = (isset($topic) && isset($topic->topic_name)) ? $topic->topic_name : '';
            $agreementCamp = $this->getLiveCamp($topicNumber, 1, ['nofilter' => true], $asOfTime, $asOf);

            $tree = [];
            $level = 1;
            $tree[$startCamp]['topic_id'] = $topicNumber;
            $tree[$startCamp]['level'] = $level;
            $tree[$startCamp]['camp_id'] = $startCamp;
            $tree[$startCamp]['camp_name'] = (isset($agreementCamp) && isset($agreementCamp->camp_name)) ? $agreementCamp->camp_name : '';
            $tree[$startCamp]['title'] = $topicName;
            $tree[$startCamp]['score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime, $nickNameId);
            $tree[$startCamp]['full_score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime, $nickNameId, true);
            $tree[$startCamp]['submitter_nick_id'] = $topic->submitter_nick_id ?? '';
            $tree[$startCamp]['children'] = $this->traverseCampTimeline($algorithm, $topicNumber, $startCamp, $rootUrl, $tree, $level, null, $asOfTime, $asOf);

            return TopicSupport::sumTranversedArraySupportCountP($tree);
        } catch (\Exception $th) {
            throw new \Exception("Prepare Camp Timeline Exception: " . $th->getMessage());
        }
    }

    public function traverseCampTimeline($algorithm, $topicNumber, $parentCamp, $rootUrl, &$lastArray, $level, $lastparent = null, $asOfTime = null, $asOf = 'default')
    {
        try {
            $key = $topicNumber . '-' . $parentCamp . '-' . $lastparent;
            if (in_array($key, $this->traversetempArray)) {
                return [];
            }
            $this->traversetempArray[] = $key;
            $childs = $this->campChildrens($topicNumber, $parentCamp);

            $array = [];
            $level++;
            foreach ($childs as $child) {
                $array[$child->camp_num]['topic_id'] = $topicNumber;
                $array[$child->camp_num]['level'] = $level;
                $array[$child->camp_num]['camp_id'] = $child->camp_num;
                $array[$child->camp_num]['camp_name'] = $child->camp_name;
                $array[$child->camp_num]['title'] = $child->camp_name;
                $array[$child->camp_num]['score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime);
                $array[$child->camp_num]['full_score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime, null, true);
                $array[$child->camp_num]['submitter_nick_id'] = $child->submitter_nick_id ?? '';
                $children = $this->traverseCampTimeline($algorithm, $child->topic_num, $child->camp_num, $rootUrl, $array, $level, $child->parent_camp_num, $asOfTime, $asOf);
                $array[$child->camp_num]['children'] = is_array($children) ? $children : [];
            }
            return $array;
        } catch (\Exception $th) {
            abort(401, "Traverse Camp Timeline Exception: " . $th->getMessage());
        }
    }

    public function campCount($nickNameId, $condition, $political = false, $topicNumber = 0, $campNumber = 0, $asOfTime = null, $topic_num = 0)
    {
        $cacheWithTime = false;
        $total = 0;

        $sql = "select count(*) as countTotal,support_order,camp_num from support where nick_name_id = $nickNameId and (" . $condition . ")";
        $sql2 = "and ((start < $asOfTime) and ((end = 0) or (end > $asOfTime)))";

        /* Cache applied to avoid repeated queries in recursion */
        if ($cacheWithTime) {
            $result = Cache::remember("$sql $sql2", 2, function () use ($sql, $sql2) {
                return DB::select("$sql $sql2");
            });
            return isset($result[0]->countTotal) ? $result[0]->countTotal : 0;
        } else {
            $result = Cache::remember("$sql", 1, function () use ($sql, $sql2) {
                return DB::select("$sql $sql2");
            });
        }

        if ($political == true && $topicNumber == 231 && ($campNumber == 2 ||  $campNumber == 3 || $campNumber == 4 || $campNumber == 6)) {
            $sqlQuery = "select count(*) as countTotal,support_order,camp_num from support where nick_name_id = $nickNameId and topic_num = " . $topicNumber . " and ((start < $asOfTime) and ((end = 0) or (end > $asOfTime)))";
            $supportCount = DB::select("$sqlQuery");
            if ($supportCount[0]->countTotal > 1 && $topic_num != 231) {
                if ($result[0]->support_order == 1) {
                    for ($i = 1; $i <= $supportCount[0]->countTotal; $i++) {
                        $supportPoint = $result[0]->countTotal;
                        if ($i == 1 || $i == $supportCount[0]->countTotal) { // adding only last reminder
                            $total = $total + round($supportPoint * 1 / (2 ** ($i)), 3);
                        }
                    }
                } else {
                    $supportPoint = $result[0]->countTotal;
                    $total = $total + round($supportPoint * 1 / (2 ** ($result[0]->support_order)), 3);
                }
            } else {
                $total = $result[0]->countTotal;
            }
        } else {
            $total = $result[0]->countTotal;
        }

        return $total;
    }

    public function campTreeCount($topicNumber, $nickNameId, $topicNum, $campNum, $asOfTime)
    {
        try {
            $expertCamp = $this->getExpertCamp($topicNumber, $nickNameId, $asOfTime);
            if (!$expertCamp) { # not an expert canonized nick.
                return 0;
            }
            $score_multiplier = $this->getMindExpertScoreMultiplier($expertCamp, $topicNumber, $nickNameId, $asOfTime);

            # start with one person one vote canonize.
            if ($topicNum == 81 || $topicNum == 124) {  // mind expert special case
                $algo = 'blind_popularity';
                if (!Arr::exists($this->sessionTempArray, "score_tree_{$topicNumber}_{$algo}")) {
                    $expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree($algo, $topicNumber, $asOfTime);
                    $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"] = $expertCampReducedTree;
                } else {
                    $expertCampReducedTree = $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"];
                }

                $total_score = 0;
                if (array_key_exists('camp_wise_tree', $expertCampReducedTree) && array_key_exists($expertCamp->camp_num, $expertCampReducedTree['camp_wise_tree']) && count($expertCampReducedTree['camp_wise_tree'][$expertCamp->camp_num]) > 0) {
                    foreach ($expertCampReducedTree['camp_wise_tree'][$expertCamp->camp_num] as $tree_node) {
                        if (count($tree_node) > 0) {
                            foreach ($tree_node as $score) {
                                $total_score = $total_score + $score['score'];
                            }
                        }
                    }
                }

                return $total_score * $score_multiplier;
            } else {
                $expertCampReducedTree = $this->mindExpertsNonSpecial($topicNumber, $nickNameId, $asOfTime, $topicNum); # only need to canonize this branch
                $total_score = 0;
                if (count($expertCampReducedTree) > 0) {
                    foreach ($expertCampReducedTree as $tree_node) {
                        if (count($tree_node) > 0) {
                            foreach ($tree_node as $score) {
                                $total_score = $total_score + $score['score'];
                            }
                        }
                    }
                }
                return $total_score;
            }
        } catch (\Exception $th) {
            throw new \Exception("Camp Tree Count with Mind Expert Algorithm Exception: " . $th->getMessage());
        }
    }

    public function getExpertCamp($topicnum, $nick_name_id, $asOfTime)
    {
        try {
            $camps = Cache::remember("$topicnum-bydate-support-$asOfTime", 2, function () use ($topicnum, $asOfTime) {
                return Camp::where('topic_num', '=', $topicnum)
                    ->where('objector_nick_id', '=', null)
                    ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicnum . ' and objector_nick_id is null group by camp_num)')
                    ->where('go_live_time', '<', $asOfTime)
                    ->orderBy('submit_time', 'desc')
                    ->groupBy('camp_num')
                    ->get();
            });

            $expertCamp = $camps->filter(function ($item) use ($nick_name_id) {
                return  $item->camp_about_nick_id == $nick_name_id;
            })->last();
            return $expertCamp;
        } catch (\Exception $th) {
            throw new \Exception("Get Expert Camp Exception: " . $th->getMessage());
        }
    }

    public function mindExpertsNonSpecial($topicNumber, $nickNameId, $asOfTime, $topicNum)
    {
        try {
            $expertCamp = $this->getExpertCamp($topicNumber, $nickNameId, $asOfTime);
            if (!$expertCamp) { # not an expert canonized nick.
                return 0;
            }

            $score_multiplier = $this->getMindExpertScoreMultiplier($expertCamp, $topicNumber, $nickNameId, $asOfTime);
            if ($topicNum == 124) {
                $algo = 'computer_science_experts';
                if (!Arr::exists($this->sessionTempArray, "score_tree_{$topicNumber}_{$algo}")) {
                    $expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree($algo, $topicNumber, $asOfTime);
                    $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"] = $expertCampReducedTree;
                } else {
                    $expertCampReducedTree = $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"];
                }
            } else {
                $algo = 'mind_experts';
                if (!Arr::exists($this->sessionTempArray, "score_tree_{$topicNumber}_{$algo}")) {
                    $expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree($algo, $topicNumber, $asOfTime);
                    $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"] = $expertCampReducedTree;
                } else {
                    $expertCampReducedTree = $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"];
                }
            }

            // Check if user supports himself
            if (array_key_exists('camp_wise_tree', $expertCampReducedTree) && array_key_exists($expertCamp->camp_num, $expertCampReducedTree['camp_wise_tree'])) {
                return $expertCampReducedTree['camp_wise_tree'][$expertCamp->camp_num];
            } else {
                return [];
            }
        } catch (\Exception $th) {
            throw new \Exception("Mind Experts Non Special Exception: " . $th->getMessage());
        }
    }

    public function getMindExpertScoreMultiplier($expertCamp, $topicNumber = 0, $nickNameId = 0, $asOfTime = null)
    {
        try {
            $key = $asOfTime ?? '';
            # Implemented cache for existing data. 
            $supports = Cache::remember("$topicNumber-supports-$key", 2, function () use ($topicNumber, $asOfTime) {
                return Support::where('topic_num', '=', $topicNumber)
                    ->whereRaw("(start < $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start', 'DESC')
                    ->select(['support_order', 'camp_num', 'topic_num', 'nick_name_id', 'delegate_nick_name_id'])
                    ->get();
            });

            $user_support_camps = Support::where('topic_num', '=', $topicNumber)
                ->whereRaw("(start < $asOfTime) and ((end = 0) or (end > $asOfTime))")
                ->where('nick_name_id', '=', $nickNameId)
                ->get();
            $topic_num_array = array();
            $camp_num_array = array();

            foreach ($user_support_camps as $scamp) {
                $topic_num_array[] = $scamp->topic_num;
                $camp_num_array[] = $scamp->camp_num;
            }

            $is_supporting_own_expert = 0;
            if (in_array($expertCamp->camp_num, $camp_num_array) && in_array($expertCamp->topic_num, $topic_num_array)) {
                $is_supporting_own_expert = 1;
            }

            $ret_camp = Camp::whereIn('topic_num', array_unique($topic_num_array))
                ->whereIn('camp_num', array_unique($camp_num_array))
                ->whereNotNull('camp_about_nick_id')
                ->where('camp_about_nick_id', '<>', 0)
                ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time < "' . $asOfTime . '" group by camp_num)')
                ->where('go_live_time', '<', $asOfTime)
                ->groupBy('camp_num')
                ->orderBy('submit_time', 'desc')
                ->get();

            $num_of_camps_supported = $ret_camp->count();
            $score_multiplier = 1;
            if (!$is_supporting_own_expert || $num_of_camps_supported > 1) {
                $score_multiplier = 5;
            }

            return $score_multiplier;
        } catch (\Exception $th) {
            throw new \Exception("Get Mind Expert Score Multiplier Exception: " . $th->getMessage());
        }
    }
}
