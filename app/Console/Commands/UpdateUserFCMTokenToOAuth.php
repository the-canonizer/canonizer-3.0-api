<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserFCMTokenToOAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update-fcm-token-to-oauth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to update user fcm token to oauth token';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = User::whereNotNull('fcm_token')->get();
        $this->withProgressBar($users, function ($user) {
            $token = $user->generateOAuthToken('fcm');
            
            $user->fcm_auth_token = $token['token'];
            $user->fcm_auth_token_expiry = time() + $token['expiry'];

            $user->save();
        });
    }
}
