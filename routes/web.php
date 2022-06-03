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

$router->get('/social/twitter/callback','UserController@twitterCallback');

$router->group(['prefix' => 'api/v3'], function() use ($router)
{
    //Api for non register users
    $router->post('/get-camp-statement','StatementController@getStatement');
    $router->post('/get-camp-newsfeed','NewsFeedController@getNewsFeed');
    $router->post('/get-topic-record','TopicController@getTopicRecord');
    $router->post('/get-camp-record','CampController@getCampRecord');
    $router->get('/get-all-namespaces','NamespaceController@getAll');
    $router->get('/get-whats-new-content','VideoPodcastController@getNewContent');
    $router->get('/get-social-media-links','SocialMediaLinkController@getLinks');
    $router->get('/get-algorithms','AlgorithmController@getAll');
    $router->get('/get-languages','ProfileController@getLanguages');
    $router->get('mobile-carrier','ProfileController@mobileCarrier');
    $router->post('/get-statement-history','StatementController@getStatementHistory');
    $router->get('user/profile/{id}','ProfileController@getUserProfile');
    $router->get('user/all-supported-camps/{id}','ProfileController@getUserSupportedCamps');
    $router->get('user/supports/{id}','ProfileController@getUserSupports');

    $router->post('/client_token','UserController@clientToken');
    //Route Group to access api with client token
    $router->group(['middleware' => 'Xss',['client', 'Xss']], function() use ($router) {
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
        $router->get('thread/list','ThreadsController@threadList');
        $router->get('post/list/{id}','ReplyController@postList');
        $router->post('/user/postVerifyEmail','UserController@postVerifyEmail');
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
        $router->post('camp/getTopicNickNameUsed','CampController@getTopicNickNameUsed');
        $router->get('camp/allAboutNickName','CampController@getAllAboutNickName');
        $router->get('/user/social/list','UserController@socialList');
        $router->post('/user/social/socialLink', ['uses' => 'UserController@socialLink']);
        $router->delete('/user/social/delete/{id}', ['uses' => 'UserController@socialDelete']);
        $router->post('/user/deactivate', ['uses' => 'UserController@deactivateUser']);
        $router->post('add-folder','UploadController@addFolder');
        $router->post('upload-files','UploadController@uploadFileToS3');
        $router->delete('/folder/delete/{id}', ['uses' => 'UploadController@folderDelete']);
        $router->get('/uploaded-files', 'UploadController@getUploadedFiles');
        $router->post('/edit-camp-newsfeed','NewsFeedController@editNewsFeed');
        $router->post('/store-camp-newsfeed','NewsFeedController@storeNewsFeed');
        $router->post('/update-camp-newsfeed','NewsFeedController@updateNewsFeed');
        $router->post('/delete-camp-newsfeed','NewsFeedController@deleteNewsFeed');
        $router->post('thread/save','ThreadsController@store');
        $router->put('thread/update/{id}','ThreadsController@update');
        $router->get('folder/files/{id}', 'UploadController@getFolderFiles');
        $router->delete('/file/delete/{id}', ['uses' => 'UploadController@FileDelete']);
        $router->post('support/add', 'SupportController@addDirectSupport');
        $router->post('support/add-delegate', 'SupportController@addDelegateSupport');
        $router->post('post/save','ReplyController@store');
        $router->put('post/update/{id}','ReplyController@update');
        $router->delete('post/delete/{id}','ReplyController@isDelete');
        $router->post('camp/subscription','CampController@campSubscription');
        $router->post('support/update','SupportController@removeSupport');
        $router->post('support/remove-delegate','SupportController@removeDelegateSupport');
        $router->post('get-activity-log','ActivityController@getActivityLog');
        $router->get('camp/subscription/list','CampController@campSubscriptionList');
        $router->post('support-order/update','SupportController@updateSupportOrder');
    });
    $router->post('/ads','AdsController@getAds');
    $router->post('/images','ImageController@getImages');
});
