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
     * Get model class name 
     * if class name plural like Languages then remove s from last 
     * 
     * @param object $classObject
     * @return string
     */

     public function getModelClassName($classObject){

        if(!is_object($classObject)){
             return '';
        }
        
        $modelName = get_class($classObject);
        $modelName = substr(strrchr($modelName, "\\"), 1);
        $lastChar = substr($modelName, -1);
        if($lastChar == 's') {
            $modelName = substr($modelName, 0, -1);
        }
        return $modelName;
     }

}
