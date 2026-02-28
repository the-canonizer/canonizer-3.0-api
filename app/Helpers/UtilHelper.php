<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class UtilHelper
{
    public function getNumberOfPages($totalCount, $pageSize)
    {
        try {
            $numberOfPages = $totalCount / $pageSize;
            $expArr = explode('.', $numberOfPages);
            $fractionalDigit = isset($expArr[1]) && $expArr[1] > 0 ? 1 : 0;
            $numberOfPages = $expArr[0] + $fractionalDigit;
            
            return $numberOfPages;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getCronRunDateString()
    {
        $cronDate = env('CS_CRON_DATE');
        $cronDate = isset($cronDate) ? strtotime($cronDate) : strtotime(date('Y-m-d'));
        return $cronDate;
    }

    public function curlExecute($type, $url, $headers=null, $body=null) {

        $options = array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => "",
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => $type,
            CURLOPT_POSTFIELDS      => $body,
            CURLOPT_REFERER         => env('APP_URL'),
            CURLOPT_HTTPHEADER      => $headers
        );

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $curlResponse = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return null;
        }
        else {
            return $curlResponse;
        }
    }

    public function getCommandRuningStatus($statement, $signature) {
        
        $commandStatus = 0; 
        
        exec("ps -ef | grep \"".$signature ."\"", $output);

        if(is_array($output) && count($output) > 0) {
            foreach($output as $row) {
                $contains = Str::contains($row, $statement);
    
                if($contains) {
                    $commandStatus = 1;
                    break;
                }
            }
        }

        return $commandStatus;
    }

    function dd() {
        $args = func_get_args();
        call_user_func_array('dump', $args);
        die();
    }

    function exceptionResponse($exception, $errorTracing = false) {
        $errResponse = [
            'error_code' => $exception->getCode() ?? 500,
            'message' => $exception->getMessage() ?? "Something went wrong",
            'data' => NULL,
            'error' => [
                'message' => $exception->getFile().' on line '.$exception->getLine(),
                'errorTraced' => []
            ]
        ];
        if($errorTracing) {
            $errResponse['error']["errorTraced"] = $exception->getTrace();
        }
        return $errResponse;
    }
}
