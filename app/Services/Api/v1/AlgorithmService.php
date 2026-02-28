<?php

namespace App\Services\Api\v1;

use App\Models\EtherAddresses;
use App\Models\Nickname;
use App\Models\SharesAlgorithm;
use App\Facades\Util;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Facades\Services\CampServiceFacade as CampService;

class AlgorithmService
{
    /**
     * @return all the available algorithm key values used in Canonizer Service
     */
    public static function getAlgorithmKeyList($default="timeline",$algo="")
    {
        if($default=="timeline"){
            if($algo!="")
              return array($algo);

            return array('blind_popularity', 'mind_experts','computer_science_experts','PhD','christian','secular','mormon','uu','atheist','transhumanist','united_utah','republican','forward_party','democrat','ether','shares','shares_sqrt','sandy_city','sandy_city_council', 'utah_forward_party');
        }
        else{
            return array('blind_popularity', 'mind_experts','computer_science_experts');
        }
    }

    public function blind_popularity($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        return 1;
    }

    public function mind_experts($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        return CampService::campTreeCount(81, $nickNameId,$topicNumber,$campNumber, $asOfTime);
    }

    public function computer_science_experts($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        return CampService::campTreeCount(124, $nickNameId, $topicNumber, $campNumber,$asOfTime);
    }

    public function PhD($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 55 and camp_num =  5) or ' .
            '(topic_num = 55 and camp_num = 10) or ' .
            '(topic_num = 55 and camp_num = 11) or ' .
            '(topic_num = 55 and camp_num = 12) or ' .
            '(topic_num = 55 and camp_num = 14) or ' .
            '(topic_num = 55 and camp_num = 15) or ' .
            '(topic_num = 55 and camp_num = 17)';

        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    public function christian($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 4) or ' .
            '(topic_num = 54 and camp_num = 5) or ' .
            '(topic_num = 54 and camp_num = 6) or ' .
            '(topic_num = 54 and camp_num = 7) or ' .
            '(topic_num = 54 and camp_num = 8) or ' .
            '(topic_num = 54 and camp_num = 9) or ' .
            '(topic_num = 54 and camp_num = 10) or ' .
            '(topic_num = 54 and camp_num = 11) or ' .
            '(topic_num = 54 and camp_num = 18)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    public function secular($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 3)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    public function mormon($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 7) or ' .
            '(topic_num = 54 and camp_num = 8) or ' .
            '(topic_num = 54 and camp_num = 9) or ' .
            '(topic_num = 54 and camp_num = 10) or ' .
            '(topic_num = 54 and camp_num = 11)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    public function uu($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 15)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    public function atheist($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 2) or ' .
            '(topic_num = 2 and camp_num = 2) or ' .
            '(topic_num = 2 and camp_num = 4) or ' .
            '(topic_num = 2 and camp_num = 5)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    public function transhumanist($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 40 and camp_num = 2) or ' .
            '(topic_num = 41 and camp_num = 2) or ' .
            '(topic_num = 42 and camp_num = 2) or ' .
            '(topic_num = 42 and camp_num = 4) or ' .
            '(topic_num = 43 and camp_num = 2) or ' .
            '(topic_num = 44 and camp_num = 3) or ' .
            '(topic_num = 45 and camp_num = 2) or ' .
            '(topic_num = 46 and camp_num = 2) or ' .
            '(topic_num = 47 and camp_num = 2) or ' .
            '(topic_num = 48 and camp_num = 2) or ' .
            '(topic_num = 48 and camp_num = 3) or ' .
            '(topic_num = 49 and camp_num = 2) ';

        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    public function united_utah($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 231 and camp_num = 2)';
        return CampService::campCount($nickNameId, $condition, true, 231, 2, $asOfTime,$topicNumber);
    }

    public function republican($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 231 and camp_num = 3)';
        return CampService::campCount($nickNameId, $condition, true, 231, 3, $asOfTime,$topicNumber);
    }

    public function forward_party($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null){
        $condition = '(topic_num = 231 and camp_num = 6)';
        return CampService::campCount($nickNameId, $condition, true, 231, 3, $asOfTime,$topicNumber);
    }

    public function democrat($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 231 and camp_num = 4)';
        return CampService::campCount($nickNameId, $condition, true, 231, 4, $asOfTime,$topicNumber);
    }

    public function ether($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $nickname = Nickname::find($nickNameId);
        $userId = null;

        if (!empty($nickname)) {
            $userId = $nickname->user_id;
        }

        $ethers = EtherAddresses::where('user_id', '=', $userId)->get();
        $totalEthers = 0;

        $method = "POST";
        $url = env('ETHER_URL');
        $apiKey = env('ETHER_KEY');
        $etherUrl = $url . $apiKey;
        $headers = array(
            "Accept-Encoding: gzip, deflate",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Content-Type: application/json",
            "Host: mainnet.infura.io",
        );

        foreach ($ethers as $ether) {
            $body = "{\"jsonrpc\":\"2.0\",\"method\":\"eth_getBalance\",\"params\": [\"$ether->address\", \"latest\"],\"id\":1}";
            $curlResponse = \App\Library\Util::curlExecute($method, $etherUrl, $headers, $body);

            if (!isset($curlResponse) || empty($curlResponse)) {
                return 0;
            }

            $curlResultObj = json_decode($curlResponse);
            $balance = $curlResultObj->result;
            $totalEthers += (hexdec($balance) / 1000000000000000000);
        }

        return $totalEthers;
    }

    public function sandy_city($nickNameId = null, $topicNumber = 0, $campNumber = 0,$asOfTime = null){
        return $this->sandy_city_algo($nickNameId);
    }

    public function sandy_city_council($nickNameId = null, $topicNumber = 0, $campNumber = 0,$asOfTime = null){
        return $this->sandy_city_council_algo($nickNameId);
    }

    public function shares($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $algo = 'shares';
        return $this->shareAlgo($nickNameId, $topicNumber, $campNumber, $algo, $asOfTime);
    }

    public function shares_sqrt($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $algo = 'shares_sqrt';
        return $this->shareAlgo($nickNameId, $topicNumber, $campNumber, $algo, $asOfTime);
    }

    public function shareAlgo($nickNameId, $topicNumber = 0, $campNumber = 0, $algo = 'shares', $asOfTime = null)
    {
        try {
            $year = date('Y', $asOfTime);
            $month = date('m', $asOfTime);

            $shares = SharesAlgorithm::whereYear('as_of_date', '=', $year)
                ->whereMonth('as_of_date', '<=', $month)
                ->where('nick_name_id', $nickNameId)
                ->orderBy('as_of_date', 'ASC')
                ->get();

            $sumOfShares = 0;
            $sumOfSqrtShares = 0;

            if (count($shares)) {
                foreach ($shares as $s) {
                    $sumOfShares = $s->share_value;
                    $sumOfSqrtShares = number_format(sqrt($s->share_value), 2);
                }
            }else{
                $latestRecord = SharesAlgorithm::where('nick_name_id',$nickNameId)->orderBy('as_of_date','desc')->first();
                if(isset($latestRecord) && isset($latestRecord->as_of_date)){
                    $as_of_time = strtotime($latestRecord->as_of_date);
                    $year = date('Y', $as_of_time);
                    $month = date('m', $as_of_time);

                    $shares = SharesAlgorithm::whereYear('as_of_date', '=', $year)
                        ->whereMonth('as_of_date', '<=', $month)
                        ->where('nick_name_id', $nickNameId)
                        ->orderBy('as_of_date', 'ASC')
                        ->get();
                    if (count($shares)) {
                        foreach ($shares as $s) {
                            $sumOfShares = $s->share_value;
                            $sumOfSqrtShares = number_format(sqrt($s->share_value), 2);
                        }
                    }
                }
            }

            if ($algo == 'shares') {
                $total = $sumOfShares;
            } else {
                $total = $sumOfSqrtShares;
            }

            return ($total > 0) ? $total : 0;
        } catch (\Exception $th) {
            throw new \Exception($th->getMessage(), 403);
        }
    }

    public function sandy_city_algo($nickNameId = null){
        $user = Nickname::getUserByNickName($nickNameId);
        $score = 0;
        if($user && $user->city !=='' && str_contains(strtolower($user->city),'sandy')){
            $score = 1;
        }
        return $score;
    }

    public function sandy_city_council_algo($nickNameId=null){
        $nick_name_list=[1,346];
        $nick_name_score_list = [1=>1,346=>1];
        $score = 0;
        if(in_array($nickNameId,$nick_name_list)){
            $score = $nick_name_score_list[$nickNameId];
        }
        return $score;
    }

    public static function utah_forward_party($nickNameId,$topicNumber = 0, $campNumber = 0, $asOfTime = null){
        $condition = '(topic_num = 231 and camp_num = 7)';
        return CampService::campCount($nickNameId,$condition,true,231,7,$asOfTime,$topicNumber);
    }

    public function getCacheAlgorithms($updateAll, $algorithm,$default="tree")
    {
        $algorithmArr = $this->getAlgorithmKeyList($default,$algorithm);

        if ($updateAll) {
            return $algorithmArr;
        }

        if (in_array($algorithm, $algorithmArr) && ($key = array_search($algorithm, $algorithmArr)) !== false) {
            return array($algorithmArr[$key]);
        }

        return $algorithmArr;
    }
}
