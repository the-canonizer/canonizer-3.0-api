<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});
$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/v3'], function() use ($router)
{
    //Api for non register users
    $router->get('/get_all_namespaces','NamespaceController@getAll');
    $router->get('/get_whats_new_content','VideoPodcastController@getNewContent');
    $router->get('/get_social_media_links','SocialMediaLinkController@getLinks');
    $router->get('/get_algorithms','AlgorithmController@getAll');
    $router->get('/get_languages','ProfileController@getLanguages');
    $router->get('mobilecarrier','ProfileController@mobileCarrier');

    $router->post('/client_token','UserController@clientToken');
    //Route Group to access api with client token
    $router->group(['middleware' => ['client', 'Xss']], function() use ($router) {
        $router->post('/register','UserController@createUser');
        $router->post('/user/login','UserController@loginUser');
        $router->post('/verifyOtp','UserController@postVerifyOtp');
        $router->post('/user/social/login','UserController@socialLogin');
        $router->post('/user/social/callback','UserController@socialCallback');
        $router->get('/country/list','UserController@countryList');
        $router->post('/forgotpassword/sendOtp','ForgotPasswordController@sendOtp');
        $router->post('/forgotpassword/verifyOtp','ForgotPasswordController@verifyOtp');
        $router->post('/forgotpassword/update','ForgotPasswordController@updatePassword');
        $router->post('/user/reSendOtp','UserController@reSendOtp');
    });

    //Route Group to access api with user access token
    $router->group(['middleware' => 'auth'], function() use ($router) {
        $router->get('/user/logout','UserController@logoutUser');
        $router->post('changepassword','ProfileController@changePassword');      
        $router->post('updateprofile','ProfileController@updateProfile');
        $router->get('user/profile','ProfileController@getProfile');
        $router->post('sendotp','ProfileController@sendOtp');
        $router->post('verifyotp','ProfileController@VerifyOtp');
        $router->post('add_nick_name','NicknameController@addNickName');
        $router->post('update_nick_name/{id}','NicknameController@UpdateNickName');
        $router->get('get_nick_name_list','NicknameController@getNickNameList');
    });

});
