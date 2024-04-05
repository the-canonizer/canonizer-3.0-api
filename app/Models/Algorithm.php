<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Helpers\SupportAndScoreCount;
use App\Facades\Util;
use App\Models\EtherAddresses;
use App\Models\Nickname;
use App\Models\ShareAlgorithm;
use DB;

class Algorithm extends Model
{
    protected $table = 'algorithms';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'algorithm_key','algorithm_label'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];


    /**
     * Get the blind_popularity algorithm score.
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $value = 1
     */

    public static function blind_popularity($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        return 1;
    }

    /**
     * Get the mind_experts algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public static function mind_experts($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        return self::campTreeCount(81, $nickNameId,$topicNumber,$campNumber, $asOfTime);
    }

    /**
     * Get the computer_science_expert algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public static function computer_science_experts($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        return self::campTreeCount(124, $nickNameId, $topicNumber, $campNumber,$asOfTime);
    }

    /**
     * Get the phd algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function PhD($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 55 and camp_num =  5) or ' .
            '(topic_num = 55 and camp_num = 10) or ' .
            '(topic_num = 55 and camp_num = 11) or ' .
            '(topic_num = 55 and camp_num = 12) or ' .
            '(topic_num = 55 and camp_num = 14) or ' .
            '(topic_num = 55 and camp_num = 15) or ' .
            '(topic_num = 55 and camp_num = 17)';

        return self::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the christian algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function christian($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
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
        return self::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the secular algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public static function secular($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 3)';
        return self::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the mormon algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function mormon($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 7) or ' .
            '(topic_num = 54 and camp_num = 8) or ' .
            '(topic_num = 54 and camp_num = 9) or ' .
            '(topic_num = 54 and camp_num = 10) or ' .
            '(topic_num = 54 and camp_num = 11)';
        return self::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the Universal Unitarian algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function uu($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 15)';
        return self::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the atheist algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function atheist($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 2) or ' .
            '(topic_num = 2 and camp_num = 2) or ' .
            '(topic_num = 2 and camp_num = 4) or ' .
            '(topic_num = 2 and camp_num = 5)';

        return self::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the Transhumanist algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public static function transhumanist($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
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

        return self::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the united_utah algorithm score.
     * United Utah Party Algorithm using related topic and camp
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public static function united_utah($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 231 and camp_num = 2)';
        return self::campCount($nickNameId, $condition, true, 231, 2, $asOfTime);
    }

    /**
     * Get the united_utah algorithm score.
     * Republican Algorithm using related topic and camp
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public static function republican($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 231 and camp_num = 3)';
        return self::campCount($nickNameId, $condition, true, 231, 3, $asOfTime);
    }

    /**
     * Get the united_utah algorithm score.
     * Democrat Algorithm using related topic and camp
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public static function democrat($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 231 and camp_num = 4)';
        return self::campCount($nickNameId, $condition, true, 231, 4, $asOfTime);
    }

    /**
     * Get the united_utah algorithm score.
     * Forward party Algorithm using related topic and camp
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function forward_party($nickNameId,$topicNumber = 0, $campNumber = 0, $asOfTime = null){
        $condition = '(topic_num = 231 and camp_num = 6)';
        return self::campCount($nickNameId,$condition,true,231,6,$asOfTime,$topicNumber);
    }


     /**
     * Get the united_utah algorithm score.
     * Sandy City Algorithm using related topic and camp
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public static function sandy_city($nickNameId,$topicNumber=0,$campNumber=0,$asOfTime = null){
        return self::sandy_city_algo($nickNameId);
    }


     /**
     * Get the united_utah algorithm score.
     * Sandy City Council Algorithm using related topic and camp
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public static function sandy_city_council($nickNameId,$topicNumber=0,$campNumber=0,$asOfTime = null){
        return self::sandy_city_council_algo($nickNameId);
    }

    /**
     * Get user ethers.
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $totalEthers
     */
    public static function ether($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {

        $nickname = Nickname::find($nickNameId);
        $userId = null;

        if (!empty($nickname) && count(array($nickname)) > 0) 
        {
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

        if(count($ethers)){
            foreach ($ethers as $ether) { // If users has multiple addresses

                $body = "{\"jsonrpc\":\"2.0\",\"method\":\"eth_getBalance\",\"params\": [\"$ether->address\", \"latest\"],\"id\":1}";
                $curlResponse = util::execute($method, $etherUrl, $headers, $body);

                if (!isset($response) || empty($response) || $response == '' || $response == null) {
                    return 0;
                }

                $curlResultObj = json_decode($curlResponse);
                $balance = $curlResultObj->result;
                $totalEthers += (hexdec($balance) / 1000000000000000000);
            }
        }

        return $totalEthers;
    }

    /**
     * Get canonizer shares algorithm score.
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function shares($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $algo = 'shares';
        return self::shareAlgo($nickNameId, $topicNumber, $campNumber, $algo, $asOfTime);
    }

    /**
     * Get canonizer canonizer algorithm score
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function shares_sqrt($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $algo = 'shares_sqrt';
        return self::shareAlgo($nickNameId, $topicNumber, $campNumber, $algo, $asOfTime);
    }

    /**
     * Get share algorithm score
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function shareAlgo($nickNameId, $topicNumber = 0, $campNumber = 0, $algo = 'shares', $asOfTime)
    {
        $year = date('Y', $asOfTime);
        $month = date('m', $asOfTime);

        $shares = ShareAlgorithm::whereYear('as_of_date', '=', $year)
            ->whereMonth('as_of_date', '<=', $month)
            ->where('nick_name_id', $nickNameId)
            ->orderBy('as_of_date', 'ASC')
            ->get();

        $sumOfShares = 0;
        $sumOfSqrtShares = 0;

        if (count($shares)) {
            foreach ($shares as $s) {
                $sumOfShares = $s->share_value; //$sumOfShares + $s->share_value;
                $sumOfSqrtShares = number_format(sqrt($s->share_value), 2); //$sumOfSqrtShares+ number_format(sqrt($s->share_value),2);
            }
        }

        $condition = "topic_num = $topicNumber and camp_num = $campNumber";
        $sql = "select count(*) as countTotal,support_order,camp_num from support where nick_name_id = $nickNameId and (" . $condition . ")";
        $sql2 = "and ((start < $asOfTime) and ((end = 0) or (end > $asOfTime)))";

        $result = Cache::remember("$sql $sql2", 2, function () use ($sql, $sql2) {
            return DB::select("$sql $sql2");
        });

        $total = 0;
        if ($algo == 'shares') {
            $total = $sumOfShares;
        } else {
            $total = $sumOfSqrtShares;
        }

        $returnShares = $total;

        return ($returnShares > 0) ? $returnShares : 0;
    }


    /**
     * Get the camp tree count.
     * @param int $topicNumber
     * @param int $nickNameId
     * @param int $topicNum
     * @param int $campNum
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function campTreeCount($topicNumber, $nickNameId,$topicNum,$campNum, $asOfTime)
    {
        $expertCamp = Camp::getExpertCamp($topicNumber,$nickNameId,$asOfTime);
        if(!$expertCamp){ # not an expert canonized nick.
            return 0;
        }
        $score_multiplier = self::getMindExpertScoreMultiplier($expertCamp,$topicNumber,$nickNameId,$asOfTime);
    
    
        # start with one person one vote canonize.    
        if($topicNum == 81){  // mind expert special case
            $SupportAndScoreCount = new SupportAndScoreCount();
            $expertCampReducedTree = $SupportAndScoreCount->getCampAndNickNameWiseSupportTree('blind_popularity',$topicNumber,$asOfTime); # only need to canonize this branch
            $total_score = 0;
            if(array_key_exists('camp_wise_tree',$expertCampReducedTree) && array_key_exists($expertCamp->camp_num,$expertCampReducedTree['camp_wise_tree']) && count($expertCampReducedTree['camp_wise_tree'][$expertCamp->camp_num]) > 0){
                foreach($expertCampReducedTree['camp_wise_tree'][$expertCamp->camp_num] as $tree_node){
                    if(count($tree_node) > 0){
                        foreach($tree_node as $score){
                            $total_score = $total_score + $score['score'];
                        }
                    }                
                }
            }  
        
            return $total_score * $score_multiplier;
        }else{
            $expertCampReducedTree = self::mindExpertsNonSpecial($topicNumber,$nickNameId,$asOfTime); # only need to canonize this branch
            $total_score = 0;
            if(count($expertCampReducedTree) > 0){
                foreach($expertCampReducedTree as $tree_node){
                    if(count($tree_node) > 0){
                        foreach($tree_node as $score){
                            $total_score = $total_score + $score['score'];
                        }
                    }                
                  }  
            }
        }

        return $total_score;
    }

     /**
     * Get the camp count .
     * @param int $nickNameId
     * @param string $condition
     * @param boolean $political
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public static function campCount($nickNameId, $condition, $political=false, $topicNumber=0, $campNumber=0, $asOfTime = null,$topic_num=0)
    {
        $cacheWithTime = false; 
        $total = 0;
         
        $sql = "select count(*) as countTotal,support_order,camp_num from support where nick_name_id = $nickNameId and (" .$condition.")";
        $sql2 ="and ((start < $asOfTime) and ((end = 0) or (end > $asOfTime)))";
         
        /* Cache applied to avoid repeated queries in recursion */
        if($cacheWithTime){
            $result = Cache::remember("$sql $sql2", 2, function () use($sql,$sql2) {
                return DB::select("$sql $sql2");
            });
        }else{
            $result = Cache::remember("$sql", 1, function () use($sql,$sql2) {
                return DB::select("$sql $sql2");
            });
        }
           
		 if($political == true && $topicNumber ==231 && ($campNumber == 2 ||  $campNumber == 3 || $campNumber == 4 || $campNumber == 6) ) {
            // get support count from topic if political party algo selected
            $sqlQuery = "select count(*) as countTotal,support_order,camp_num from support where nick_name_id = $nickNameId and topic_num = ".$topicNumber." and ((start < $asOfTime) and ((end = 0) or (end > $asOfTime)))";	
            $supportCount = DB::select("$sqlQuery");
            if($supportCount[0]->countTotal > 1 && $topic_num!=231){
                // echo "<pre>"; print_r($supportCount);print_r($result);
                if($result[0]->support_order == 1){
                    for($i=1; $i<=$supportCount[0]->countTotal; $i++){
                        $supportPoint = $result[0]->countTotal;
                        if($i == 1 || $i == $supportCount[0]->countTotal){ // adding only last reminder
                            $total = $total + round($supportPoint * 1 / (2 ** ($i)), 3);
                        }
                    }
                }else{
                    $supportPoint = $result[0]->countTotal;
                    $total = $total + round($supportPoint * 1 / (2 ** ($result[0]->support_order)), 3);
                }
                
             }else{
                $total = $result[0]->countTotal;
            }     	
			
		 } else {
			$total = $result[0]->countTotal; 
		 }	
        return  $total;
    }

     /**
     * Get the camp tree count.
     * @param $expertCamp
     * @param int $topicNumber
     * @param int $nickNameId
     * @param int $asOfTime
     * @return int $score_multiplier
     */
    public static function getMindExpertScoreMultiplier($expertCamp,$topicNumber=0,$nickNameId=0,$asOfTime)
    {
        $key = '';
		if(isset($_REQUEST['asof']) && $_REQUEST['asof']=='bydate'){
            $key = $asOfTime;
		}
        
		# Implemented cache for existing data. 
        $supports = Cache::remember("$topicNumber-supports-$key", 2, function () use($topicNumber,$asOfTime) {
                 return Support::where('topic_num','=',$topicNumber)
                    ->whereRaw("(start < $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start','DESC')
                    ->select(['support_order','camp_num','topic_num','nick_name_id','delegate_nick_name_id'])
                    ->get();
        });

        $num_of_camps_supported = 0;
        $user_support_camps = Support::where('topic_num','=',$topicNumber)
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
        if(in_array($expertCamp->camp_num,$camp_num_array) && in_array($expertCamp->topic_num,$topic_num_array)){
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

        if ($ret_camp->count()) {
            $num_of_camps_supported = $ret_camp->count();
        }
        $score_multiplier = 1;
        if(!$is_supporting_own_expert || $num_of_camps_supported > 1) {
            $score_multiplier = 5; 
         }

        return $score_multiplier;        
    }

     /**
     * Get the camp tree count.
     * @param int $topicNumber
     * @param int $nickNameId
     * @param int $asOfTime
     *
     * @return int $camp_wise_score_tree
     */

    public static function mindExpertsNonSpecial($topicNumber,$nickNameId,$asOfTime)
    {
        $expertCamp = Camp::getExpertCamp($topicNumber,$nickNameId,$asOfTime);
        if(!$expertCamp){ # not an expert canonized nick.
            return 0;
        }

        $SupportAndScoreCount = new SupportAndScoreCount();
        $score_multiplier = self::getMindExpertScoreMultiplier($expertCamp, $topicNumber, $nickNameId,$asOfTime);
        $expertCampReducedTree = $SupportAndScoreCount->getCampAndNickNameWiseSupportTree('mind_experts',$topicNumber,$asOfTime); # only need to canonize this branch
        
        // Check if user supports himself
        if(array_key_exists('camp_wise_tree',$expertCampReducedTree) && array_key_exists($expertCamp->camp_num,$expertCampReducedTree['camp_wise_tree'])){
            return $expertCampReducedTree['camp_wise_tree'][$expertCamp->camp_num];

        }
        
        return [];
        
    }

     /**
     * Get the camp count .
     * @param int $nickNameId
     * @return int $score
     */

    public static function sandy_city_algo($nickNameId){
        $user=Nickname::getUserByNickName($nickNameId);
        $score = 0;
        if($user && $user->city !=='' && str_contains(strtolower($user->city),'sandy')){
            $score = 1;
        }
        return $score;

    }

    /**
     * Get the camp count .
     * @param int $nickNameId
     * @return int $score
     */

    public static function sandy_city_council_algo($nickNameId){
        $nick_name_list=[1,346];
        $nick_name_score_list = [1=>1,346=>1];
        $score = 0;
        if(in_array($nickNameId,$nick_name_list)){
            $score = $nick_name_score_list[$nickNameId];
        }
        return $score;

    }
}
