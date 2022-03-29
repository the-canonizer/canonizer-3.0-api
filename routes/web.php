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
    $router->post('/get-camp-statement','StatementController@getStatement');
    $router->post('/get-camp-newsfeed','NewsFeedController@getNewsFeed');
    $router->post('/edit-camp-newsfeed','NewsFeedController@editNewsFeed');
    $router->post('/update-camp-newsfeed','NewsFeedController@updateNewsFeed');
    $router->post('/get-topic-record','TopicController@getTopicRecord');
    $router->post('/get-camp-record','CampController@getCampRecord');

    $router->get('/get-all-namespaces','NamespaceController@getAll');
    $router->get('/get-whats-new-content','VideoPodcastController@getNewContent');
    $router->get('/get-social-media-links','SocialMediaLinkController@getLinks');
    $router->get('/get-algorithms','AlgorithmController@getAll');
    $router->get('/get-languages','ProfileController@getLanguages');
    $router->get('mobile-carrier','ProfileController@mobileCarrier');

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
        $router->post('change-password','ProfileController@changePassword');      
        $router->post('update-profile','ProfileController@updateProfile');
        $router->get('user/profile','ProfileController@getProfile');
        $router->post('send-otp','ProfileController@sendOtp');
        $router->post('verify-otp','ProfileController@VerifyOtp');
        $router->post('add-nick-name','NicknameController@addNickName');
        $router->post('update-nick-name/{id}','NicknameController@UpdateNickName');
        $router->get('get-nick-name-list','NicknameController@getNickNameList');
        $router->post('camp/save','CampController@store');
        $router->post('topic/save','TopicController@store');
        $router->get('get-direct-supported-camps','SupportController@getDirectSupportedCamps');
        $router->get('get-delegated-supported-camps','SupportController@getDelegatedSupportedCamps');
        $router->post('camp/allParent','CampController@getAllParentCamp');
        $router->get('camp/allAboutNickName','CampController@getAllAboutNickName');
        $router->get('/user/social/list','UserController@socialList');
        $router->post('/user/social/socialLink', ['uses' => 'UserController@SocialLink']);
        $router->delete('/user/social/delete/{id}', ['uses' => 'UserController@socialDelete']);
    });

    $router->post('/ads','AdsController@getAds');
    $router->post('/images','ImageController@getImages');
});
