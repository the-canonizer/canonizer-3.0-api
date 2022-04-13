<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

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

    public function getTopicCampUrl($topic_num,$camp_num,$topic,$camp,$currentTime = null):string
    {
        $urlPortion = self::getSeoBasedUrlPortion($topic_num,$camp_num,$topic,$camp); 
       
        $urlPortion = $urlPortion.'?currentTime='.$currentTime.'';
        
        return env('APP_URL_FRONT_END').('/topic/' .$urlPortion);
    }

    /**
     * @param $topic_num
     * @param $camp_num
     * @param $topic
     * @param $camp
     * @return string
     */

    public function getSeoBasedUrlPortion($topic_num,$camp_num,$topic,$camp):string
    {
        $topic_name = '';
        $camp_name = '';
        if($topic && isset($topic->topic_name)){
                $topic_name = ($topic->topic_name !='') ? $topic->topic_name: $topic->title;
        }
        if($camp && isset($camp->camp_name)){
              $camp_name = $camp->camp_name;
            }
        $topic_id_name = $topic_num;
        $camp_num_name = $camp_num;
        if($topic_name!=''){
            $topic_id_name = $topic_num . "-" . preg_replace('/[^A-Za-z0-9\-]/', '-',$topic_name);
        }
        if($camp_name!=''){
                $camp_num_name = $camp_num."-".preg_replace('/[^A-Za-z0-9\-]/', '-', $camp->camp_name);
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

}
