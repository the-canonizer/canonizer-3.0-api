<?php

namespace App\Helpers;


use Aws\S3\S3Client;

class Aws
{

    /**
     * @param $url
     * @param $data
     * @return object
     */
    public static function createS3Client():object
    {
       
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_REGION'),
            'credentials' => [
            'key'    => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY')
            ]
        ]);

        return $s3Client;
    }

}
