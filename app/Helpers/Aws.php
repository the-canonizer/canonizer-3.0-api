<?php

namespace App\Helpers;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;

class Aws
{

    /**
     * 
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

    /**
     * @return object
     */
    public static function uploadFile($filename, $file)
    {
        $s3Client = self::createS3Client();
                    
        $result = $s3Client->putObject([
             'Bucket' => env('AWS_BUCKET'),
             'Key'    => $filename,
             'Body'   => fopen($file, 'r'),
         ]);

        return $result;
    }

    public static function deleteFile($filename)
    {
        return self::createS3Client()->deleteObject([
            'Bucket' => env('AWS_BUCKET'),
            'Key'    => $filename
        ]);
    }

    public static function doesObjectExist($filename)
    {
        try {
            $result = self::createS3Client()->headObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key'    => $filename,
            ]);

            return true;
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() === 'NotFound') {
                return false;
            }

            throw $e;
        }
    }

}
