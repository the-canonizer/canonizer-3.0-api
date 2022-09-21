<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use App\Models\Nickname;

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

        //$this->registerPolicies();
        \Dusterio\LumenPassport\LumenPassport::routes($this->app);

        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            if ($request->input('api_token')) {
                return User::where('api_token', $request->input('api_token'))->first();
            }
        });

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
