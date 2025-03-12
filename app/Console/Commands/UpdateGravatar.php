<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Helpers\Aws;
use Exception;
use Throwable;

class UpdateGravatar extends Command
{
    protected $signature = 'update-gravatar';
    protected $description = 'Update Gravatar images for all registered users';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $users = User::orderBy('id', 'desc')->where(function ($query) {
                $query->whereNull('profile_picture_path')
                ->orWhere('profile_picture_path', '');
    })
    ->get();

        foreach ($users as $user) {
            $gravatarUrl = $this->getGravatar($user->email);

            if ($gravatarUrl) {
                $user->update(['profile_picture_path' =>$gravatarUrl]);
                $this->info("Updated Gravatar for: {$user->id} {$user->email}");
            } else {
                $this->warn("No Gravatar found for: {$user->id} {$user->email}");
            }
        }

        $this->info('Gravatar update complete.');
    }

    private function getGravatar($email)
    {
        $emailHash = md5(strtolower(trim($email)));
        $gravatarUrl = "https://www.gravatar.com/avatar/$emailHash?d=404";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $gravatarUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $imageData = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($imageData === "404 Not Found" || empty($contentType)) {
            return null;
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/svg+xml' => 'svg',
        ];

        if (!isset($extensions[$contentType])) {
            $this->info("Unknown content type $contentType for gravatar", ['email' => $email]);
            return null;
        }

        $extension = $extensions[$contentType];
        $filename = 'profile/' . $emailHash . '.' . $extension;
        $tempFile = tempnam(sys_get_temp_dir(), 'gravatar');
        file_put_contents($tempFile, $imageData);

        try {
            Aws::uploadFile($filename, $tempFile, [
                'ACL' => 'public-read',
                'ContentType' => $contentType
            ]);
        } catch (Exception $e) {
            $this->warn("Error uploading gravatar to S3", ['email' => $email, 'exception' => $e]);
            return null;
        } finally {
            unlink($tempFile);
        }

        return urlencode($filename);
    }
}
