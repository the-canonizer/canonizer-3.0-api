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
use App\Models\Namespaces;
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


    /**
     * @param $id
     * @return String
     */
    public static function canon_encode($id=''):string
    {
        $code = 'Malia' . $id . 'Malia';
        $code = base64_encode($code);
        return $code;
    }

    /**
     * @param $code
     * @return int
     */
    public static function canon_decode($code = ''):int
    {
        $code = base64_decode($code);
        return (int) $code=str_replace("Malia","",$code);
    }

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

    public static function generateShortCode($strength = 9) {
        $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
    
        return  "can-" . $random_string;
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
    public function dispatchJob($topic, $campNum = 1, $updateAll = 0) {

        try{
            $selectedAlgo = 'blind_popularity';
            $asOf = 'default';
            $asOfDefaultDate = time();
            $canonizerServiceData = [
                'topic_num' =>  $topic->topic_num,
                'algorithm' => $selectedAlgo,
                'asOfDate'  => $asOfDefaultDate,
                'asOf'      => $asOf,
                'updateAll' => $updateAll
            ];
            // Dispatch job when create a camp/topic
            dispatch(new CanonizerService($canonizerServiceData))->onQueue(env('QUEUE_SERVICE_NAME'));

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
                            'updateAll' => 1
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
                $subject = 'canon/sandbox/';
            }
            if(preg_match('/sandbox testing/i',$namespace->name)){
                $subject = 'canon/sandbox testing/';
            }
            if(env('APP_ENV') == 'staging' || env('APP_ENV') == 'local' || env('APP_ENV') == 'development'){
               return '[local.' . $subject . ']';
            }else{
              return  '[' . $subject . ']';
            }
          
        } catch (Exception $ex) {
            Log::error("Util :: GetEmailSubjectForSandbox :: message: " . $ex->getMessage());
        }
    }
}
