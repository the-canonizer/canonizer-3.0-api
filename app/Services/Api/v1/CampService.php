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

    public function getLiveCamp(int $topicNumber, int $campNumber, array $filter = array(), ?int $asOfTime = null, string $asOf = 'default')
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

    public static function getCampCreatedDate(int $campNumber, int $topicNumber)
    {
        return Camp::where('topic_num', $topicNumber)
            ->where('camp_num', $campNumber)
            ->pluck('submit_time')
            ->first();
    }

    public function campChildrens(int $topicNumber, int $parentCamp)
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

    public function getCamptSupportCount(string $algorithm, int $topicNumber, int $campNumber, ?int $asOfTime = null, ?int $nickNameId = null, bool $full_score = false)
    {
        $asOfTime = $asOfTime ?? time();
        $algo = $algorithm;
        $key = "score_tree_{$topicNumber}_{$algo}";
        
        if (!Arr::exists($this->sessionTempArray, $key)) {
            $score_tree = $this->getCampAndNickNameWiseSupportTree($algo, $topicNumber, $asOfTime);
            $this->sessionTempArray[$key] = $score_tree;
        } else {
            $score_tree = $this->sessionTempArray[$key];
        }

        $total_score = 0;
        if (array_key_exists('camp_wise_tree', $score_tree) && array_key_exists($campNumber, $score_tree['camp_wise_tree'])) {
            foreach ($score_tree['camp_wise_tree'][$campNumber] as $order => $tree_node) {
                if (count($tree_node) > 0) {
                    foreach ($tree_node as $nick => $score) {
                        $delegate_arr = $score_tree['nick_name_wise_tree'][$nick][$order][$campNumber];
                        $delegate_score = $this->getDelegatesScore($delegate_arr, $full_score);
                        
                        if ($full_score) {
                            $delegate_full_score = $this->getDelegatesFullScore($delegate_arr);
                            $total_score += $score['full_score'] + $delegate_full_score;
                        } else {
                            $total_score += $score['score'] + $delegate_score;
                        }
                    }
                }
            }
        }

        return $total_score;
    }

    public function prepareCampTree(string $algorithm, int $topicNumber, ?int $asOfTime = null, int $startCamp = 1, string $rootUrl = '', ?int $nickNameId = null, string $asOf = 'default', int $fetchTopicHistory = 0)
    {
        try {
            $this->traversetempArray = [];

            if (!Arr::exists($this->sessionTempArray, "topic-support-nickname-{$topicNumber}")) {
                $nickNameSupport = Support::where('topic_num', '=', $topicNumber)
                    ->where('delegate_nick_name_id', 0)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start', 'DESC')
                    ->groupBy('nick_name_id')
                    ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
                    ->get();
                $this->sessionTempArray["topic-support-nickname-{$topicNumber}"] = $nickNameSupport;
            }

            if (!Arr::exists($this->sessionTempArray, "topic-support-{$topicNumber}")) {
                $topicSupport = Support::where('topic_num', '=', $topicNumber)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start', 'DESC')
                    ->select(['support_order', 'camp_num', 'nick_name_id', 'delegate_nick_name_id', 'topic_num'])
                    ->get();
                $this->sessionTempArray["topic-support-{$topicNumber}"] = $topicSupport;
            }

            if($asOf == 'review') {
                $topicChild = Camp::where('topic_num', '=', $topicNumber)
                                ->where('camp_name', '!=', 'Agreement')
                                ->where('objector_nick_id', '=', null)
                                ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and grace_period = 0 group by camp_num)')
                                ->groupBy('camp_num')
                                ->orderBy('submit_time', 'desc')
                                ->get();
            } else {
                $topicChild = Camp::where('topic_num', '=', $topicNumber)
                                ->where('camp_name', '!=', 'Agreement')
                                ->where('objector_nick_id', '=', null)
                                ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time <= ' . $asOfTime . ' group by camp_num)')
                                ->where('go_live_time', '<=', $asOfTime)
                                ->groupBy('camp_num')
                                ->orderBy('submit_time', 'desc')
                                ->get();
            }
            $this->sessionTempArray["topic-child-{$topicNumber}"] = $topicChild;

            $topic = (new TopicService())->getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false], $asOf, $fetchTopicHistory);
            $reviewTopic = (new TopicService())->getReviewTopic($topicNumber);
            
            $topicName = (isset($topic) && isset($topic->topic_name)) ? $topic->topic_name : '';
            $reviewTopicName = (isset($reviewTopic) && isset($reviewTopic->topic_name)) ? $reviewTopic->topic_name : $topicName;
            
            $agreementCamp = $this->getLiveCamp($topicNumber, 1, ['nofilter' => true], $asOfTime, $asOf);
            $isDisabled = 0; $isOneLevel = 0; $isArchive = 0; $directArchive = 0;
            if (!empty($agreementCamp)) {
                $isDisabled = $agreementCamp->is_disabled ?? 0;
                $isOneLevel = $agreementCamp->is_one_level ?? 0;
                $isArchive  = $agreementCamp->is_archive ?? 0;
                $directArchive = $agreementCamp->direct_archive ?? 0;
            }

            $tree = [];
            $level = 1;
            $tree[$startCamp]['topic_id'] = $topicNumber;
            $tree[$startCamp]['level'] = $level;
            $tree[$startCamp]['camp_id'] = $startCamp;
            $tree[$startCamp]['camp_name'] = (isset($agreementCamp) && isset($agreementCamp->camp_name)) ? $agreementCamp->camp_name : '';
            $tree[$startCamp]['title'] = $topicName;
            $tree[$startCamp]['review_title'] = $reviewTopicName;
            $tree[$startCamp]['link'] = $rootUrl . '/' . $this->getTopicCampUrl($topicNumber, $startCamp, $asOfTime);
            $tree[$startCamp]['review_link'] = $rootUrl . '/' . $this->getTopicCampUrl($topicNumber, $startCamp, $asOfTime, true);
            $tree[$startCamp]['score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime, $nickNameId);
            $tree[$startCamp]['full_score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime, $nickNameId, true);
            $tree[$startCamp]['submitter_nick_id'] = $topic->submitter_nick_id ?? '';
            
            $topicCreatedDate = TopicService::getTopicCreatedDate($topicNumber);
            $tree[$startCamp]['created_date'] = $topicCreatedDate ?? 0;
            $tree[$startCamp]['is_valid_as_of_time'] = $asOfTime >= $topicCreatedDate ? true : false;
            $tree[$startCamp]['is_disabled'] = $isDisabled;
            $tree[$startCamp]['is_one_level'] = $isOneLevel;
            $tree[$startCamp]['is_archive'] = $isArchive;
            $tree[$startCamp]['direct_archive'] = $directArchive;
            $tree[$startCamp]['subscribed_users'] = $this->getTopicCampSubscriptions($topicNumber, $startCamp);
            $tree[$startCamp]['topic_tags'] = TopicTag::getRelatedTagIds($topicNumber);

            $tree[$startCamp]['support_tree'] = $this->getSupportTree($algorithm, $topicNumber, $startCamp, $asOfTime, $asOf);
            $tree[$startCamp]['children'] = $this->traverseCampTree($algorithm, $topicNumber, $startCamp, $rootUrl, $tree, $level, null, $asOfTime, $asOf);
            
            $result = TopicSupport::sumTranversedArraySupportCountP($tree);
            return $result;
        } catch (\Exception $th) {
            Log::error("Prepare Camp Tree Exception: " . $th->getMessage() . " at " . $th->getFile() . ":" . $th->getLine());
            throw new \Exception("Prepare Camp Tree Exception: " . $th->getMessage());
        }
    }

    public function traverseCampTree(string $algorithm, int $topicNumber, int $parentCamp, string $rootUrl, array &$lastArray, int $level, ?int $lastparent = null, ?int $asOfTime = null, string $asOf = 'default')
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
                $oneCamp = $this->getLiveCamp($child->topic_num, $child->camp_num, ['nofilter' => true], $asOfTime, $asOf);
                $reviewCamp = (new TopicService())->getReviewTopic($child->topic_num); // This should be review camp, but TopicService only has getReviewTopic.
                // In dev_service, it calls getReviewCamp. Let's use getReviewCamp if I add it.
                $reviewCamp = $this->getReviewCamp($child->topic_num, $child->camp_num);
                $reviewCampName = (isset($reviewCamp) && isset($reviewCamp->camp_name)) ? $reviewCamp->camp_name : $oneCamp->camp_name;

                $array[$child->camp_num]['topic_id'] = $topicNumber;
                $array[$child->camp_num]['level'] = $level;
                $array[$child->camp_num]['camp_id'] = $child->camp_num;
                $array[$child->camp_num]['camp_name'] = $child->camp_name;
                $array[$child->camp_num]['title'] = $child->camp_name;
                $array[$child->camp_num]['review_title'] = $reviewCampName;

                $queryString = (app('request')->getQueryString()) ? '?' . app('request')->getQueryString() : "";
                $array[$child->camp_num]['link'] = $rootUrl . '/' . $this->getTopicCampUrl($child->topic_num, $child->camp_num, $asOfTime) . $queryString . '#statement';
                $array[$child->camp_num]['review_link'] = $rootUrl . '/' . $this->getTopicCampUrl($child->topic_num, $child->camp_num, $asOfTime, true) . $queryString . '#statement';
                
                $array[$child->camp_num]['score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime);
                $array[$child->camp_num]['full_score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime, null, true);
                $array[$child->camp_num]['submitter_nick_id'] = $child->submitter_nick_id ?? '';
                $array[$child->camp_num]['created_date'] = $oneCamp->submit_time ?? 0;
                $array[$child->camp_num]['is_disabled'] = $child->is_disabled ?? 0;
                $array[$child->camp_num]['is_one_level'] = $child->is_one_level ?? 0;
                $array[$child->camp_num]['is_archive'] = $child->is_archive ?? 0;
                $array[$child->camp_num]['direct_archive'] = $child->direct_archive ?? 0;
                $array[$child->camp_num]['support_tree'] = $this->getSupportTree($algorithm, $child->topic_num, $child->camp_num, $asOfTime);
                $array[$child->camp_num]['subscribed_users'] = $this->getTopicCampSubscriptions($child->topic_num, $child->camp_num);

                if($child->parent_camp_num == 1) {
                    $parentCampLive = (new TopicService())->getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);
                } else {
                    $parentCampLive = $this->getLiveCamp($child->topic_num, $child->parent_camp_num, ['nofilter' => true], $asOfTime, $asOf);
                }
                
                // Set the implicit subscription of the parent camp.
                $implicitParentSubscriptionArray = $this->changeArrayExplicity($array[$child->camp_num]['subscribed_users'], $child->camp_name, $child->camp_num);
                $lastArray[$child->parent_camp_num]['subscribed_users'] = ($lastArray[$child->parent_camp_num]['subscribed_users'] ?? []) + $implicitParentSubscriptionArray;
                
                $array[$child->camp_num]['parent_camp_is_disabled'] = $parentCampLive->is_disabled ?? 0;
                $array[$child->camp_num]['parent_camp_is_one_level'] = $parentCampLive->is_one_level ?? 0;

                $children = $this->traverseCampTree($algorithm, $child->topic_num, $child->camp_num, $rootUrl, $array, $level, $child->parent_camp_num, $asOfTime, $asOf);
                $array[$child->camp_num]['children'] = is_array($children) ? $children : [];
            }
            return $array;
        } catch (\Exception $th) {
            Log::error("Traverse Camp Tree Exception: " . $th->getMessage());
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

    public function getTopicCampUrl($topicNumber, $campNumber, $asOfTime, $isReview = false)
    {
        try {
            $urlPortion = $this->getSeoBasedUrlPortion($topicNumber, $campNumber, $asOfTime, $isReview);
            return ('topic/' . $urlPortion);
        } catch (\Exception $th) {
            return "topic/$topicNumber/$campNumber";
        }
    }

    public function getSeoBasedUrlPortion($topicNumber, $campNumber, $asOfTime, $isReview)
    {
        try {
            $topic_name = '';
            $camp_name = '';
            $topic_id_name = $topicNumber;
            $camp_num_name = $campNumber;

            $topic = (new TopicService())->getLiveTopic($topicNumber, $asOfTime, ['nofilter' => true]);
            $camp = $this->getLiveCamp($topicNumber, $campNumber, ['nofilter' => true], $asOfTime);

            if ($topic && isset($topic->topic_name)) {
                $topic_name = ($topic->topic_name != '') ? $topic->topic_name : $topic->title;
            }
            if ($camp && isset($camp->camp_name)) {
                $camp_name = $camp->camp_name;
            }

            if ($isReview) {
                $ReviewTopic = (new TopicService())->getReviewTopic($topicNumber, $asOfTime, ['nofilter' => true]);
                $ReviewCamp = $this->getReviewCamp($topicNumber, $campNumber);
                if ($ReviewTopic && isset($ReviewTopic->topic_name)) {
                    $topic_name = ($ReviewTopic->topic_name != '') ? $ReviewTopic->topic_name : $ReviewTopic->title;
                }
                if ($ReviewCamp && isset($ReviewCamp->camp_name)) {
                    $camp_name = $ReviewCamp->camp_name;
                }
            }

            if ($topic_name != '') {
                $topic_id_name = $topicNumber . "-" . preg_replace('/[^A-Za-z0-9\-]/', '-', $topic_name);
            }
            if ($camp_name != '') {
                $camp_num_name = $campNumber . "-" . preg_replace('/[^A-Za-z0-9\-]/', '-', $camp_name);
            }

            return $topic_id_name . '/' . $camp_num_name;
        } catch (\Exception $th) {
            return "$topicNumber/$campNumber";
        }
    }

    public function getReviewCamp($topicNumber, $campNumber)
    {
        try {
            $reviewCamp = Camp::where('topic_num', $topicNumber)
                ->where('camp_num', '=', $campNumber)
                ->where('grace_period', 0)
                ->where('objector_nick_id', '=', null)
                ->orderBy('go_live_time', 'desc')->first();
            return $reviewCamp;
        } catch (\Exception $th) {
            return null;
        }
    }

    public function getSupportTree(string $algorithm, int $topicNum, int $campNum, ?int $asOfTime = null, string $asOf = 'default'){
        try{
            if(!Arr::exists($this->sessionTempArray, "score_tree_{$topicNum}_{$algorithm}")) {
                $score_tree = $this->getCampAndNickNameWiseSupportTree($algorithm, $topicNum, $asOfTime);
                $this->sessionTempArray["score_tree_{$topicNum}_{$algorithm}"] = $score_tree;        
            } else {
                $score_tree = $this->sessionTempArray["score_tree_{$topicNum}_{$algorithm}"];
            }
        
            $supports = Support::where('topic_num', '=', $topicNum)
                        ->join("nick_name","nick_name.id", "=", "support.nick_name_id")
                        ->where('delegate_nick_name_id', 0)
                        ->where('camp_num', '=', $campNum)
                        ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                        ->orderBy('camp_num','ASC')->orderBy('support_order','ASC')
                        ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num', 'nick_name'])
                        ->get();

            $array = [];
            $liveTopic = (new TopicService())->getLiveTopic($topicNum,$asOfTime, ['nofilter'=>true]);
            $liveCamp = $this->getLiveCamp($topicNum, $campNum, [], $asOfTime, $asOf);
            $namespaceId = (isset($liveTopic->namespace_id) && $liveTopic->namespace_id ) ? $liveTopic->namespace_id : 1; 

            foreach($supports as $key =>$support){            
                $array[$support->nick_name_id] = [
                        'score' => 0,
                        'support_order' => $support->support_order,
                        'nick_name' => $support->nick_name,
                        'nick_name_id' => $support->nick_name_id,
                        'nick_name_link' => Nickname::getNickNameLink($support->nick_name_id, $namespaceId, $topicNum, $campNum),
                        'delegates' => [],
                        'camp_leader' => ($liveCamp && $liveCamp->camp_leader_nick_id > 0 && $liveCamp->camp_leader_nick_id == $support->nick_name_id),
                    ];
               
                $currentCampSupport = 0;
                $supportPoint=0;
                $supportFullPoint=0;
                $multiSupport = false;
                $supportOrder = 0;
                $delegateTree = [];
                
                if(array_key_exists('nick_name_wise_tree',$score_tree) && isset($score_tree['nick_name_wise_tree'][$support->nick_name_id]) && count($score_tree['nick_name_wise_tree'][$support->nick_name_id]) > 0){
                    $multiSupport = count($score_tree['nick_name_wise_tree'][$support->nick_name_id]) > 1 ? true: false;
                    foreach($score_tree['nick_name_wise_tree'][$support->nick_name_id] as $supp_order=>$tree_node){
                        if(count($tree_node) > 0){
                            foreach($tree_node as $camp_num=>$camp_score){                          
                                if($camp_num == $campNum){
                                    $currentCampSupport = 1;
                                    $supportOrder = $supp_order;
                                    $delegateTree = $camp_score['delegates'] ?? [];               
                                    $supportPoint = $supportPoint + $camp_score['score'];
                                    $supportFullPoint = $supportFullPoint + $camp_score['full_score'];
                                    break; 
                                }
                            }
                        }
                    }
                }
               
                if($currentCampSupport){
                    $array[$support->nick_name_id]['score'] = $supportPoint;
                    $array[$support->nick_name_id]['full_score'] = $supportFullPoint;
                    $array[$support->nick_name_id]['delegates'] = $this->traverseChildTree($algorithm, $topicNum, $campNum, $support->nick_name_id, $supportOrder, $multiSupport, $delegateTree, $asOfTime, $namespaceId);
                }
            }
            $array = TopicSupport::sumTranversedArraySupportCountP($array);
            return array_values($array); // Return as indexed array for JSON
        }catch(\Exception $th){
            return [];
        }
    }

    public function traverseChildTree(string $algorithm, int $topicNum, int $campNum, int $delegateNickId, int $parentSupportOrder, bool $multiSupport, array $delegateTree = [], ?int $asOfTime = null, int $namespaceId = 1)
    {
        $delegatedSupports = Support::where('topic_num', '=', $topicNum)
                    ->join("nick_name","nick_name.id", "=", "support.nick_name_id")
                    ->where('delegate_nick_name_id', '=', $delegateNickId)
                    ->where('camp_num', '=', $campNum)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('camp_num','ASC')->orderBy('support_order','ASC')
                    ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num', 'nick_name'])
                    ->get();
        
        $array = [];
        foreach($delegatedSupports as $support){ 
            if($support->camp_num == $campNum && isset($delegateTree[$support->nick_name_id])){ 
                    $array[$support->nick_name_id]['score'] =$delegateTree[$support->nick_name_id]['score'];
                    $array[$support->nick_name_id]['full_score'] =$delegateTree[$support->nick_name_id]['full_score'];
                    $array[$support->nick_name_id]['support_order'] = $support->support_order;
                    $array[$support->nick_name_id]['nick_name'] = $support->nick_name;
                    $array[$support->nick_name_id]['nick_name_id'] = $support->nick_name_id;
                    $array[$support->nick_name_id]['nick_name_link'] = Nickname::getNickNameLink($support->nick_name_id, $namespaceId, $topicNum, $campNum);
                    $array[$support->nick_name_id]['delegate_nick_name_id'] = $support->delegate_nick_name_id;
                    $delegateArr = $delegateTree[$support->nick_name_id]['delegates'] ?? [];
                    $liveCamp = $this->getLiveCamp($topicNum, $campNum, [], $asOfTime);
                    $array[$support->nick_name_id]['camp_leader'] = ($liveCamp && $liveCamp->camp_leader_nick_id > 0 && $liveCamp->camp_leader_nick_id == $support->nick_name_id);
                    $array[$support->nick_name_id]['delegates'] = $this->traverseChildTree($algorithm, $topicNum, $campNum, $support->nick_name_id, $parentSupportOrder, $multiSupport,$delegateArr, $asOfTime, $namespaceId);
                }  
            }
            return array_values($array);
    }

    public function getTopicCampSubscriptions(int $topicNumber, int $campNumber) {
        try {
            $campSubscriptionsArr = [];
            $campSubscriptions = CampSubscription::where([['topic_num','=',$topicNumber],
                ['camp_num','=',$campNumber]])->whereNull('subscription_end')->pluck('user_id')->toArray();
            if (count($campSubscriptions) > 0) {
                $explicitArr = array("explicit" => true);
                $campSubscriptionsArr = array_fill_keys($campSubscriptions, $explicitArr);
            } 
            return $campSubscriptionsArr;
        } catch (\Exception $th) {
            return [];
        }
    }

    public function changeArrayExplicity(array $childCampSubscribers, string $title, int $campNumber, bool $explicity = false) {
        $newArr = [];
        foreach ($childCampSubscribers as $key => $value) {
            $newValue = $value;
            $newValue['explicit'] = $explicity;
            if (!$explicity) {
                $newValue['child_camp_name'] = $title;
                $newValue['child_camp_id'] = $campNumber;
            }
            $newArr[$key] = $newValue;
        }
        return $newArr;
    }

    public function getDelegatesFullScore($tree)
    {
        $score = 0;
        if (isset($tree['delegates']) && count($tree['delegates']) > 0) {
            foreach ($tree['delegates'] as $nick => $delScore) {
                $score += $delScore['full_score'];
                if (isset($delScore['delegates']) && count($delScore['delegates']) > 0) {
                    $score += $this->getDelegatesFullScore($delScore);
                }
            }
        }
        return $score;
    }

    public function getDelegatesScore($tree, $full_score = false)
    {
        $score = 0;
        if (isset($tree['delegates']) && count($tree['delegates']) > 0) {
            foreach ($tree['delegates'] as $nick => $delScore) {
                $score += $delScore['score'];
                if ($full_score) {
                    $score += $delScore['full_score'];
                }
                if (isset($delScore['delegates']) && count($delScore['delegates']) > 0) {
                    $score += $this->getDelegatesScore($delScore, $full_score);
                }
            }
        }
        return $score;
    }

    public function getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdate, $namespaceId, $nickNameIds, $search = '', $isCount = false, $archive = 0, $sort = false, $topic_tags = [])
    {

        $returnTopics = [];

        try {

            $returnTopics = DB::table('topic')
            ->select(DB::raw('(select count(topic_support.id) from topic_support where topic_support.topic_num = c1.topic_num) as support'), 'c1.topic_num', 
                'c1.camp_num', 'c1.title', 'c1.go_live_time', 'c1.submitter_nick_id', 't1.namespace_id')
            ->from('topic as t1')
            ->where(['t1.objector_nick_id' => null, 't1.grace_period' => 0])
            ->where('t1.go_live_time', '=', function ($query) use ($asofdate, $asof) {
                $query->from('topic as t2')->selectRaw('max(t2.go_live_time)')->whereColumn('t2.topic_num', 't1.topic_num')
                ->where([
                    ['t2.objector_nick_id', null],
                    ['t2.grace_period', 0],
                ]);
                if ($asof != 'review') {
                    $query->where('t2.go_live_time', '<=', $asofdate);
                }
            })
            ->when($namespaceId, fn ($query, $namespaceId) => $query->where('t1.namespace_id', $namespaceId))
            ->when($search, fn ($query, $search) => $query->where('t1.topic_name', 'like', '%' . $search . '%'))
            
            ->when(($topic_tags), function ($query) use ($topic_tags) {
                $query->join('topics_tags', 't1.topic_num', '=', 'topics_tags.topic_num')
                      ->whereIn('topics_tags.tag_id', $topic_tags)
                      ->groupBy('t1.topic_num');
            })
 
            ->join('camp as c1', function ($join) use ($asofdate, $asof, $archive, $nickNameIds) {
                $join->on('c1.topic_num', '=', 't1.topic_num')
                ->where(['c1.camp_num' => 1, 'c1.objector_nick_id' => null, 'c1.grace_period' => 0, 'c1.is_archive' => $archive, 'c1.direct_archive' => $archive])
                    ->where('c1.go_live_time', '=', function ($query) use ($asofdate, $asof) {
                        $query->from('camp as c2')->selectRaw('max(c2.go_live_time)')->whereColumn('c2.topic_num', 't1.topic_num')
                        ->where([
                            ['c2.camp_num', 1],
                            ['c2.objector_nick_id', null],
                            ['c2.grace_period', 0]
                        ]);
                        if ($asof != 'review') {
                            $query->where('c2.go_live_time', '<=', $asofdate);
                        }
                    })
                    ->when($nickNameIds, function ($query, $nickNameIds) {
                        $query->whereIn('c1.submitter_nick_id', $nickNameIds);
                    });
            });

            $returnTopics->latest('support');
            if (isset($sort) &&  $sort) {
                $returnTopics->orderBy('t1.id', 'DESC');
            } else {
                $returnTopics->orderBy('t1.topic_name', 'DESC');
            }

            if ($isCount) {
                return $returnTopics->count();
            }

            return $returnTopics
                ->skip($skip)
                ->take($pageSize)
                ->get();
        } catch (\Throwable $th) {
            throw new \App\Exceptions\Camp\AgreementCampsException("Exception in GetAgreementCamp:" . $th->getMessage());
        }
    }
}
