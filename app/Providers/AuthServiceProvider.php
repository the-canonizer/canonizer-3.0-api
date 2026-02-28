<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Nickname;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        Passport::tokensExpireIn(now()->addMonths(6));
        Passport::refreshTokensExpireIn(now()->addMonths(7));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Note: In Laravel 11, we may need to use a separate migrations or commands to manage scopes if needed.

        // gate to check nicnameId belonges to authorized user only 
        Gate::define('nickname-check', function (User $user, $nickNameId) {
            $allNickNames = Nickname::getNicknamesIdsByUserId($user->id);
            if(!in_array($nickNameId, $allNickNames)){
                return false;
            }
            return true;
        });
    }
}
