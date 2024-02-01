<?php

namespace App\Helpers;

use Exception;
use App\Models\Camp;
use App\Models\Topic;
use App\Models\Support;
use Illuminate\Http\Request;
use App\Jobs\CanonizerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Events\SupportAddedMailEvent;
use Illuminate\Support\Facades\Event;
use App\Events\SupportRemovedMailEvent;
use App\Models\Namespaces;
use App\Models\User;
use Throwable;
use App\Jobs\PurposedToSupportersMailJob;
use App\Models\Nickname;
use Carbon\Carbon;
use App\Jobs\TimelineJob;
use App\Events\UnarchiveCampMailEvent;

class Util
{

    /**
     * @param $url
     * @param $data
     * @return object
     */
    public function httpPost($url, $data):object
    {
        $response = Http::asForm()->post($url, $data);

        $status = $response->status();
        
        switch($status){
            case 200:
                $returnObject = (object)[
                    "status_code" => 200,
                    "message"     => "Success",
                    "error"       => null,
                    "data"        => $response->json()
                ];
                break;
            case 401:
                $returnObject = (object)[
                    "status_code" => 401,
                    "message"     => "Unauthenticated",
                    "error"       => null,
                    "data"        => null
                ];
                break;
            default :
                $returnObject = (object)[
                    "status_code" => 400,
                    "message"     => "Something went wrong",
                    "error"       => null,
                    "data"        => null
                ];
        }
        return $returnObject;
    }


    // /**
    //  * @param $id
    //  * @return String
    //  */
    // public static function canon_encode($id=''):string
    // {
    //     $code = 'Malia' . $id . 'Malia';
    //     $code = base64_encode($code);
    //     return $code;
    // }

    // /**
    //  * @param $code
    //  * @return int
    //  */
    // public static function canon_decode($code = ''):int
    // {
    //     $code = base64_decode($code);
    //     return (int) $code=str_replace("Malia","",$code);
    // }

    /**
     * @param $name
     * @return array
     */
    public function split_name($name):array
    {
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim( preg_replace('#'.preg_quote($last_name,'#').'#', '', $name ) );
        return [
            ucwords($first_name),
            ucwords($last_name)
        ];
    }

    /**
     * @param $topic_num
     * @param $camp_num
     * @param $topic
     * @param $camp
     * @param $currentTime
     * @return string
     */

    public static function getTopicCampUrl($topic_num,$camp_num,$topic,$camp,$currentTime = null):string
    {
        $urlPortion = self::getSeoBasedUrlPortion($topic_num,$camp_num,$topic,$camp); 

        if(isset($currentTime) && $currentTime)
        {
            $urlPortion = $urlPortion.'?currentTime='.$currentTime.'';
        }              
        return config('global.APP_URL_FRONT_END').('/topic/' .$urlPortion);
    }

    /**
     * @param $topic_num
     * @param $camp_num
     * @param $topic
     * @param $camp
     * @param $currentTime
     * @return string
     */

    public static function getTopicCampUrlWithoutTime($topic_num,$camp_num,$topic,$camp,$currentTime = null):string
    {
        $urlPortion = self::getSeoBasedUrlPortion($topic_num,$camp_num,$topic,$camp); 
        return config('global.APP_URL_FRONT_END').('/topic/' .$urlPortion);
    }

    /**
     * @param $topic_num
     * @param $camp_num
     * @param $topic
     * @param $camp
     * @return string
     */

    public static function getSeoBasedUrlPortion($topic_num,$camp_num,$topic,$camp):string
    {
        $topic_name = '';
        $camp_name = '';
        if($topic && isset($topic->topic_name)){
                $topic_name = ($topic->topic_name !='') ? $topic->topic_name: $topic->title;
        }
       
        $camp_name = ($camp && isset($camp->camp_name)) ? $camp->camp_name : 'Agreement';

        $topic_id_name = $topic_num;
        $camp_num_name = $camp_num;
        $regex  = '/[^A-Za-z0-9\-]/';
        if($topic_name!=''){
            $title = preg_replace($regex, '-', $topic_name);
            $topic_id_name = $topic_num . "-" . $title;
        }
        if($camp_name!=''){
            $campName = preg_replace($regex, '-', $camp_name);
            $camp_num_name = $camp_num . "-" . $campName;
        }
        return $topic_id_name . '/' . $camp_num_name;
    }

    /**
     * @param $paginator
     * @return ?object
     */

    public function getPaginatorResponse($paginator): ?object
    {
        if(empty($paginator)){
            return null;
        }
        return (object)[
            "items" => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'per_page' => (int) $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total_rows' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
    
    /**  
     * 
     * @return string
    */

    public static function generateShortCode($file, $shortCode = '') 
    {
        if(!$shortCode) {			
            $shortCode = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);	
        } 
        
        return $shortCode;
    }

    public static function topicHistoryLink($topicNum, $campNum = 1, $title, $campName = 'Aggreement' , $type)
    {
        $regex  = '/[^A-Za-z0-9\-]/';
        $title = preg_replace($regex, '-', $title);
        $campName = preg_replace($regex, '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" . $campName;
       
        return ($type == "topic") ? config('global.APP_URL_FRONT_END') . '/topic/history/' . $topicId : config('global.APP_URL_FRONT_END') . '/camp/history/' . $topicId . '/' . $campId;
    }

     /**
     * Excute the http calls 
     * @param string $type (GET|POST|PUT|DELETE)
     * @param string $url
     * @param string $headers (Optional)
     * @param array $body (Optional)
     * @return mixed
     */
    public function execute($type, $url, $headers=null, $body=null) {
        $options = array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => "",
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => $type,
            CURLOPT_POSTFIELDS      => $body,
            CURLOPT_HTTPHEADER      => $headers
        );
        
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $curl_response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return null;
        } 
        else {
            //$curl_result_obj = $curl_response;
            return $curl_response;
        }
    }

    /**
     * Dispatch canonizer service job
     * @param object $topic
     * @param boolean $updateAll
     * @return void
     */
    public function dispatchJob($topic, $campNum = 1, $updateAll = 0, $delay = null, $additionalInfo = []) {

        try{
            $selectedAlgo = 'blind_popularity';
            $asOf = 'default';
            $asOfDefaultDate = time();
            $canonizerServiceData = [
                'topic_num' =>  $topic->topic_num,
                'algorithm' => $selectedAlgo,
                'asOfDate'  => $asOfDefaultDate,
                'asOf'      => $asOf,
                'updateAll' => $updateAll,
                'camp_num'  => $campNum,
                'isUniqueJob' => true,
                'additional_info' => $additionalInfo,
                'endpointCSStore' => env('CS_STORE_TREE')
            ];
            // Dispatch job when create a camp/topic
            if ($delay) {
                // Job delay coming in seconds, update the service asOfDate for delay job execution.
                $delayTime = Carbon::now()->addSeconds($delay);
                $canonizerServiceData['asOfDate'] = $delayTime->timestamp;
                $canonizerServiceData['isUniqueJob'] = false;
                dispatch((new CanonizerService($canonizerServiceData))->delay($delayTime))->onQueue(env('DELAY_QUEUE_SERVICE_NAME'));
            } else {
                dispatch(new CanonizerService($canonizerServiceData))->onQueue(env('QUEUE_SERVICE_NAME'));
            }
            
            // Incase the topic is mind expert then find all the affected topics 
            if($topic->topic_num == config('global.mind_expert_topic_num')) {
                $camp = Camp::where('topic_num', $topic->topic_num)->where('camp_num', '=', $campNum)->where('go_live_time', '<=', time())->latest('submit_time')->first();
                if(!empty($camp)) {
                    // Get submitter nick name id
                    $submitterNickNameID = $camp->camp_about_nick_id;
                    $affectedTopicNums = Support::where('nick_name_id',$submitterNickNameID)->where('end',0)->distinct('topic_num')->pluck('topic_num');
                    foreach($affectedTopicNums as $affectedTopicNum) {
                        $topic = Topic::where('topic_num', $affectedTopicNum)->get()->last();
                        $canonizerServiceData = [
                            'topic_num' => $topic->topic_num,
                            'algorithm' => $selectedAlgo,
                            'asOfDate'  => $asOfDefaultDate,
                            'asOf'      => $asOf,
                            'updateAll' => 1,
                            'camp_num'  => $campNum,
                            'additional_info' => $additionalInfo,
                            'endpointCSStore' => env('CS_STORE_TREE')
                        ];
                        // Dispact job when create a camp
                        dispatch(new CanonizerService($canonizerServiceData))->onQueue(env('QUEUE_SERVICE_NAME'));
                           // ->unique(Topic::class, $topic->topic_num);
                    }
                }
            }
        } catch(Exception $ex) {
            Log::error("Util :: DispatchJob :: message: ".$ex->getMessage());
        }
        
    }

     /**
     * @param $items
     * @param $perPage
     * @param $page
     * @param $options
     * @return ?object
     */

    public function paginate($items, $perPage, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public static function convertUnixToDateFormat($unix_time) {
        return date('m/d/Y, h:i:s A', $unix_time);
    }

    public static function convertDateFormatToUnix($dateTime) {
        return strtotime($dateTime);
    }
    public function getEmailSubjectForSandbox($namespace_id)
    {
        try {
            $subject = 'canon';
            $namespace = Namespaces::find($namespace_id);
            if(preg_match('/sandbox/i',$namespace->name)){
                $subject = 'canon >> sandbox';
            }
            if(preg_match('/sandbox testing/i',$namespace->name)){
                $subject = 'canon >> sandbox testing';
            }
            if($subject == 'canon/sandbox testing'){
                $subject = str_replace("canon/sandbox testing", "canon >> sandbox testing", $subject);
            }else if($subject == 'canon/sandbox'){
                $subject = str_replace("canon/sandbox", "canon >> sandbox", $subject);
            }
            if(env('APP_ENV') == 'staging'){
                return '[staging.' . $subject . ']';
            }
            if(env('APP_ENV') == 'local' || env('APP_ENV') == 'development'){
               return '[local.' . $subject . ']';
            }else{
              return  '[' . $subject . ']';
            }
          
        } catch (Exception $ex) {
            Log::error("Util :: GetEmailSubjectForSandbox :: message: " . $ex->getMessage());
        }
    }

    public static function mailSubscribersAndSupporters($directSupporter, $subscribers, $link, $dataObject, $action='')
    {
        $alreadyMailed = [];
        if (!empty($directSupporter)) {
            foreach ($directSupporter as $supporter) {
                $supportData = $dataObject;
                $user = Nickname::getUserByNickName($supporter->nick_name_id);
                $alreadyMailed[] = $user->id;
                $topic = Topic::where('topic_num', '=', $supportData['topic_num'])->latest('submit_time')->get();
                $topic_name_space_id = isset($topic[0]) ? $topic[0]->namespace_id : 1;
                $nickName = Nickname::find($supporter->nick_name_id);
                $supported_camp = $nickName->getSupportCampList($topic_name_space_id, ['nofilter' => true]);
                $supported_camp_list = $nickName->getSupportCampListNamesEmail($supported_camp, $supportData['topic_num'], $supportData['camp_num']);
                $supportData['support_list'] = $supported_camp_list;
                $ifalsoSubscriber = Camp::checkifSubscriber($subscribers, $user);
                $data['namespace_id'] =  $topic_name_space_id;
                if ($ifalsoSubscriber) {
                    $supportData['also_subscriber'] = 1;
                    $supportData['sub_support_list'] = Camp::getSubscriptionList($user->id, $supportData['topic_num'], $supportData['camp_num']);
                }
                try {
                    if($action == 'add'){
                        Event::dispatch(new SupportAddedMailEvent($user->email ?? null, $user, $supportData));
                    }else if($action=='remove'){
                        Event::dispatch(new SupportRemovedMailEvent($user->email ?? null, $user, $supportData));
                    }else{
                        dispatch(new PurposedToSupportersMailJob($user, $link, $supportData,$user->email ?? null))->onQueue(env('QUEUE_SERVICE_NAME'));
                    }
                } catch (Throwable $e) {
                    echo  $e->getMessage();
                }
            }
        }
        if (!empty($subscribers)) {
            foreach ($subscribers as $usr) {
                $subscriberData = $dataObject;
                $userSub = User::find($usr);
                if (!in_array($userSub->id, $alreadyMailed, TRUE)) {
                    $alreadyMailed[] = $userSub->id;
                    $subscriptions_list = Camp::getSubscriptionList($userSub->id, $subscriberData['topic_num'], $subscriberData['camp_num']);
                    $subscriberData['support_list'] = $subscriptions_list;
                    $subscriberData['subscriber'] = 1;
                    $topic = Topic::getLiveTopic($subscriberData['topic_num']);
                    $data['namespace_id'] = $topic->namespace_id;
                    try {
                        if($action == 'add'){
                            Event::dispatch(new SupportAddedMailEvent($userSub->email ?? null, $userSub, $subscriberData));
                        }else if($action =='remove'){
                            Event::dispatch(new SupportRemovedMailEvent($userSub->email ?? null, $userSub, $subscriberData));
                        }else{
                            dispatch(new PurposedToSupportersMailJob($userSub, $link, $subscriberData,$userSub->email ?? null))->onQueue(env('QUEUE_SERVICE_NAME'));
                        }
                    } catch (Throwable $e) {
                        echo  $e->getMessage();
                    }
                }
            }
        }
        return;
    }

    public function checkParentCampChanged($all, $in_review_status, $liveCamp)
    {      
        if($all['parent_camp_num'] != $all['old_parent_camp_num']) 
        {
                $topicNum = $all['topic_num'];
                $allParentCamps = Camp::getAllParent($liveCamp);
                $supporterNicknames = Support::where('topic_num', $all['topic_num'])
                    ->where('end', 0)
                    ->whereIn('camp_num', $allParentCamps)
                    ->pluck('nick_name_id');

                $allChildCamps = Camp::getAllLiveChildCamps($liveCamp);

                if (sizeof($supporterNicknames) > 0) {
                    foreach ($allParentCamps as $parentCamp) {
                        $supportData = Support::where('topic_num',$topicNum)
                                        ->where('camp_num',$parentCamp)
                                        ->whereIn('nick_name_id',$supporterNicknames)
                                        ->where('end','=',0);
                        $results = $supportData->get();

                        $results_child = [];
                        $supportData_child = Support::where('topic_num',$topicNum)
                                        ->whereIn('camp_num',$allChildCamps)
                                        ->whereIn('nick_name_id',$supporterNicknames)
                                        ->where('end','=',0);

                        $results_child = $supportData_child->get()->toArray();                      

                        foreach($results as $value) { 
                            //if child camp have same supportter of parent camp then remove supportter from parent
                            if(!empty($results_child)){ 
                                if(array_search($value->nick_name_id, array_column($results_child, 'nick_name_id')) !== FALSE) { //found
                                   Support::removeSupportWithDelegates($all['topic_num'], $parentCamp, $value->nick_name_id); 
                                   Support::reOrderSupport($all['topic_num'], [$value->nick_name_id]);
                                } 
                            }
                        } 
                    }
                }
        }
        return;
    }

     /**
     * This function only work when we changes parent camp.
     * @param int $campChangeId
     */
    public function parentCampChangedBasedOnCampChangeId($changeID) {
        $camp = Camp::where('id', $changeID)->first();
        if(!empty($camp)) {
            $topic_num = $camp->topic_num;
            $camp_num = $camp->camp_num;
            $parent_camp_num = $camp->parent_camp_num;
            $in_review_status=true;
            $filter['topicNum'] = $topic_num;
            $filter['campNum'] = $camp_num;
            //We have fetched new live camp record
            $liveCamp = Camp::getLiveCamp($filter); 
            $all['parent_camp_num'] = $camp->parent_camp_num;
            $all['topic_num'] = $topic_num;
            $all['old_parent_camp_num']= -1;//$parent_camp_num;
            $this->checkParentCampChanged($all, $in_review_status, $liveCamp);
            $topic = $camp->topic;
            // Dispatch Job
            if(isset($topic)) {
                Util::dispatchJob($topic, $camp->camp_num, 1);
            }
        }
        return;
    }

    /**
     * This function only work when we changes parent camp.
     * @param int $campChangeId
     */
    public function getCampByChangeId($changeID) {
        $camp = Camp::where('id', $changeID)->first();
        if(!empty($camp)) {
            return $camp;
        }
        else{
            return [];
        }
    }


    function remove_emoji($string)
    {
        $symbols = "\x{1F100}-\x{1F1FF}" // Enclosed Alphanumeric Supplement
            . "\x{1F300}-\x{1F5FF}" // Miscellaneous Symbols and Pictographs
            . "\x{1F600}-\x{1F64F}" //Emoticons
            . "\x{1F680}-\x{1F6FF}" // Transport And Map Symbols
            . "\x{1F900}-\x{1F9FF}" // Supplemental Symbols and Pictographs
            . "\x{2600}-\x{26FF}" // Miscellaneous Symbols
            . "\x{2700}-\x{27BF}"; // Dingbats

        return preg_replace('/[' . $symbols . ']+/u', '', $string);
    }

    public function allow_emoji($string)
    {
        $unicodes = config('global.emoji_unicodes');
        return preg_match('/[\x{' . implode('}\x{', $unicodes) . '}]/u', $string) ? $string : '';
    }

    public static function replaceSpecialCharacters($topic_name)
    {
        $text = preg_replace('/[^A-Za-z0-9\-]/', '-',  $topic_name);
        return preg_replace("/\-\-+/", '-', $text);
    }

    public static function getSupportLink($urlPortion)
    {
        return config('global.APP_URL_FRONT_END').('/support/' .$urlPortion);
    }

    public function logMessage($message, $type = 'access')
    {
        if (env('APP_DEBUG') == true) {
            switch ($type) {
                case 'access' :
                    Log::info($message);
                    break;
                case 'error' :
                    Log::error($message);
                    break;
            }
        }
    }

    public function linkForEmail($string): string
    {
        if (empty(env('EMAIL_DOMAIN_URL'))) {
            return $string;
        }
        return str_replace(env('APP_URL_FRONT_END'), env('EMAIL_DOMAIN_URL'), $string);
    }

    /**
     * @param $link
     * @return string
     */
    public static function makeActivityRelativeURL($link):string 
    {
        $activityLink = parse_url($link);
        $relativePath = '';
        if(!empty($activityLink["path"])) {
            $relativePath = $activityLink["path"];

            if (isset($activityLink['query']) && !empty($activityLink['query'])) {
                $relativePath .= '?' . $activityLink['query'];
            }
        }
        return $relativePath;
    }

     /**
     * Dispatch Timeline job
     * @param object $topic
     * @return void
     */
    public function dispatchTimelineJob($topic_num, $campNum = 1, $updateAll = 0, $message=null, $type=null,$id=null,$old_parent_id=null, $new_parent_id=null,$delay=null,$asOfDefaultDate=null, $timeline_url=null) {

      
        try{
            $selectedAlgo = 'blind_popularity'; //blind_popularity
            $asOf = 'default';
            $asOfDefaultDate =isset($asOfDefaultDate)? $asOfDefaultDate : time();
          
            $canonizerServiceData = [
                'topic_num' =>  $topic_num,
                'algorithm' => $selectedAlgo,
                'asOfDate'  => $asOfDefaultDate,
                'asOf'      => $asOf,
                'updateAll' => $updateAll,
                'camp_num'  => $campNum,
                'message' => $message,
                'type' => $type,
                'old_parent_id' => $old_parent_id,
                'new_parent_id' => $new_parent_id,
                'isUniqueJob' => false,
                'endpointCSStore' => env('CS_STORE_TIMELINE'),
                'id' => $id,
                'url'=>$timeline_url
            ];
            if ($delay) {
                // Job delay coming in seconds, update the service asOfDate for delay job execution.
                $delayTime = Carbon::now()->addSeconds($delay);
                $canonizerServiceData['asOfDate'] = $delayTime->timestamp;
            }
            dispatch(new TimelineJob($canonizerServiceData))->onQueue(env('QUEUE_SERVICE_NAME'));
            // Incase the topic is mind expert then find all the affected topics 
            if($topic_num == config('global.mind_expert_topic_num')) {
                $camp = Camp::where('topic_num', $topic_num)->where('camp_num', '=', $campNum)->where('go_live_time', '<=', time())->latest('submit_time')->first();
                if(!empty($camp)) {
                    // Get submitter nick name id
                    $submitterNickNameID = $camp->camp_about_nick_id;
                    $affectedTopicNums = Support::where('nick_name_id',$submitterNickNameID)->where('end',0)->distinct('topic_num')->pluck('topic_num');
                    foreach($affectedTopicNums as $affectedTopicNum) {
                        $topic = Topic::where('topic_num', $affectedTopicNum)->get()->last();
                        $canonizerServiceData = [
                            'topic_num' => $topic_num,
                            'algorithm' => $selectedAlgo,
                            'asOfDate'  => $asOfDefaultDate,
                            'asOf'      => $asOf,
                            'updateAll' => 1,
                            'camp_num'  => $campNum,
                            'message' => $message,
                            'type' => $type,
                            'old_parent_id' => $old_parent_id,
                            'new_parent_id' => $new_parent_id,
                            'endpointCSStore' => env('CS_STORE_TIMELINE'),
                            'id' => $id,
                            'url'=>$timeline_url
                        ];
                        // Dispact job when create a camp
                        dispatch(new TimelineJob($canonizerServiceData))->onQueue(env('QUEUE_SERVICE_NAME'));
                    }
                }
            }
        } catch(Exception $ex) {
            Log::error("Util :: dispatchTimelineJob :: message: ".$ex->getMessage());
        }
        
    }

    /**
     * camp Archive
     */
    public function updateArchivedCampAndSupport($camp, $archiveFlag = null,  $preArchiveStatus = null)
    {
        $allchilds = Camp::getAllLiveChildCamps($camp, True);
        $topic = Topic::getLiveTopic($camp->topic_num);  // live topic
        if($archiveFlag === 1){            
            $supporterNickNames = Support::getSupportersNickNameIdInCamps($camp->topic_num, $allchilds);
           
            if (($key = array_search($camp->camp_num, $allchilds)) !== false) {
                Support::removeSupportByCamps($camp->topic_num, [$allchilds[$key]], $reason = trans('message.camp.camp_archived'), $reason_summary = trans('message.camp.camp_archived_direct_summary'));
                unset($allchilds[$key]);
            }
            Support::removeSupportByCamps($camp->topic_num, $allchilds, $reason = trans('message.camp.camp_archived'), $reason_summary = trans('message.camp.camp_archived_indirectly_summary'));
            foreach($supporterNickNames as $nickNameId){
                $nickNames = Nickname::getAllNicknamesByNickId($nickNameId);
                Support::reOrderSupport($camp->topic_num, $nickNames);
            }           
            Camp::archiveChildCamps($camp->topic_num, $allchilds);

            //job
            Util::dispatchJob($topic, 1, 1);

            if($archiveFlag!=$preArchiveStatus){
                //timeline start
                $topic = Topic::getLiveTopic($camp->topic_num, 'default');
                $nickName = Nickname::getNickName($camp->submitter_nick_id)->nick_name;
                $timelineMessage = $nickName . " archived a camp " . $camp->camp_name;
                $delayCommitTimeInSeconds = (1*10); //  10 seconds for delay job

                $timeline_url = $this->getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $camp->camp_num, $camp->camp_name, $topic->topic_name, "archive_camp", null, $topic->namespace_id, $topic->submitter_nick_id);

                $this->dispatchTimelineJob($topic->topic_num, $camp->camp_num, 1, $timelineMessage, "archive_camp", $camp->camp_num, null, null, $delayCommitTimeInSeconds, time(), $timeline_url);
                //end timeline 
            }
        }

        if($archiveFlag === 0 && $archiveFlag!=$preArchiveStatus){
            $supportToBeRevoked = Support::getSupportToBeRevoked($camp->topic_num);
            //echo "<pre>"; print_r($allchilds);
            $directArchive = 0;
            Camp::archiveChildCamps($camp->topic_num, $allchilds, $archiveFlag, $directArchive);
            $supporterNickNames = Support::getSupportersNickNameOfArchivedCamps($camp->topic_num, $allchilds);
            
            if(count($supportToBeRevoked)){
                foreach($supportToBeRevoked as $sp)
                {   $supportOrder = 0;
                    $lastSupportOrder = Support::getLastSupportOrderInTopicByNickId($camp->topic_num, $sp->nick_name_id);
                    if(!empty($lastSupportOrder)){
                            $supportOrder =  $lastSupportOrder->support_order + 1; 
                    }else{
                            $supportOrder =  1; 
                    }
                    $delegatedNickNameId = $sp->delegate_nick_name_id; 
                    TopicSupport::addSupport($sp->topic_num, $sp->camp_num, $supportOrder, $sp->nick_name_id, $delegatedNickNameId, trans('message.camp.camp_unarchived'), trans('message.camp.camp_unarchived_summary'),null);
                   
                     //send email
                    $user = Nickname::getUserByNickName($sp->nick_name_id);
                    $nickname =  Nickname::getNickName($sp->nick_name_id);
                    $topicNum = $camp->topic_num;
                    $campNum = $camp->camp_num;
                    $topicFilter = ['topicNum' => $topicNum];
                    $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
                    $topic = Camp::getAgreementTopic($topicFilter);
                                        
                    $object = $topic->topic_name ." >> ".$camp->camp_name;
                    $topicLink  =  Topic::topicLink($topic->topic_num, 1, $topic->title);
                    $campLink   =  Topic::topicLink($topic->topic_num, $camp->camp_num, $topic->title, $camp->camp_name);
                    $seoUrlPortion = Util::getSeoBasedUrlPortion($topicNum, $campNum, $topic, $camp);
                    $data['object']     = $object;
                    $data['subject']    = "Camp Unarchived - " . $object. ".";
                    $data['topic']      = $topic;
                    $data['camp']       = $camp;
                    $data['camp_name']  = $camp->camp_name;
                    $data['topic_name'] = $topic->topic_name;
                    $data['topic_num']  = $topic->topic_num;
                    $data['camp_num']   = $camp->camp_num;
                    $data['topic_link'] = $topicLink;
                    $data['camp_link']  = $campLink;   
                    $data['camp_url']   = $campLink;
                    $data['url_portion'] =  $seoUrlPortion;
                    $data['nick_name_id'] = $nickname->id;
                    $data['nick_name'] = $nickname->nick_name;
                    $data['namespace_id'] = isset($topic->namespace_id) ? $topic->namespace_id : 1;
                    $data['nick_name_link'] = Nickname::getNickNameLink($data['nick_name_id'], $data['namespace_id'], $data['topic_num'], $data['camp_num']);;
                    $data['support_action'] = 'add'; //default will be 'added'                                         
                    Event::dispatch(new UnarchiveCampMailEvent($user->email ?? null, $user, $data));
                }
                Util::dispatchJob($topic, 1, 1);
            }

            //end old support permanently
            Support::setSupportToIrrevokable($camp->topic_num, $allchilds, true);
            if($archiveFlag!=$preArchiveStatus){
                //timeline start
                //$topic = Topic::getLiveTopic($camp->topic_num, 'default');            
                $nickName = Nickname::getNickName($camp->submitter_nick_id)->nick_name;
                $timelineMessage = $nickName . " unarchived a camp ". $camp->camp_name;
                $delayCommitTimeInSeconds = (1*10); //  10 seconds for delay job

                $timeline_url = $this->getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $camp->camp_num, $camp->camp_name, $topic->topic_name, "unarchived_camp", null, $topic->namespace_id, $topic->submitter_nick_id);

                $this->dispatchTimelineJob($topic->topic_num, $camp->camp_num, 1, $timelineMessage, "unarchived_camp", $camp->camp_num, null, null, $delayCommitTimeInSeconds, time(), $timeline_url);
            }
        }

        return;
    } 

     /**
     * Get the url.
     *
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     * @param boolean $isReview
     *
     * @return string url
     */

    public function getTimelineUrlgetTimelineUrl($topic_num, $topic_name, $camp_num, $camp_name, $topicTitle, $type, $rootUrl, $namespaceId, $topicCreatedByNickId)
    {
        try {
            $topic_name =isset($topic_name)?$topic_name:$topicTitle;
            $camp_num =isset($camp_num)?$camp_num:1;
            $camp_name =isset($camp_name)?$camp_name:"Agreement";
            if($type =="create_topic" || $type =="create_camp" || $type=="archive_camp" || $type=="unarchived_camp" || $type=="direct_support_added" || $type=="direct_support_removed" || $type=="delegate_support_start" || $type=="delegate_support_removed"){
                $urlPortion =  '/topic/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name) . '/' . $camp_num . '-' . $this->replaceSpecialCharacters($camp_name);

            }
            else if($type =="update_topic"){
                $urlPortion =  '/topic/history/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name);

            }
            else if($type =="update_camp"  || $type =="parent_change"){
                $urlPortion =  '/camp/history/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name). '/' . $camp_num . '-' . $this->replaceSpecialCharacters($camp_name);

            }
            else{
                //$urlPortion = '/user/supports/' . $topicCreatedByNickId.'?topicnum='. $topic_num .'&campnum='. $camp_num .'&canon='.$namespaceId;
                //$urlPortion =  '/support/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name). '/' . $camp_num . '-' . $this->replaceSpecialCharacters($camp_name);
                $urlPortion =  '/topic/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name) . '/' . $camp_num . '-' . $this->replaceSpecialCharacters($camp_name);
            }
            return $urlPortion;

        } catch (CampURLException $th) {
             throw new CampURLException("URL Exception");
         }
    }
}
