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

$router->get('/social/twitter/callback',['uses' => 'UserController@twitterCallback']);

$router->group(['prefix' => 'api/v3'], function() use ($router)
{
    //Api for non register users

    $router->post('/client-token','UserController@clientToken');
    $router->post('/embedded-code-tracking','EmbeddedCodeController@createEmbeddedCodeTracking');

    $router->get('/search','SearchController@getSearchResults');
    $router->post('/search-filter','SearchController@advanceSearchFilter');
    $router->post('/dump-data-to-elasticsearch','SearchController@importDataToElasticSearch');
    $router->post('/meta-tags', 'MetaTagController@getMetaTags');
    //Route Group to access api with client token
    $router->group(['middleware' => ['Xss','client']], function() use ($router) {
        $router->post('/register','UserController@createUser');
        $router->post('/user/login',['uses' => 'UserController@loginUser', 'middleware' => 'checkstatus']);
        $router->post('/post-verify-otp','UserController@postVerifyOtp');
        $router->post('/user/social/login','UserController@socialLogin');
        $router->post('/user/social/callback',['uses'=>'UserController@socialCallback']);
        $router->get('/country/list','UserController@countryList');
        $router->post('/forgot-password/send-otp','ForgotPasswordController@sendOtp');
        $router->post('/forgot-password/verify-otp','ForgotPasswordController@verifyOtp');
        $router->post('/forgot-password/update','ForgotPasswordController@updatePassword');
        $router->post('/user/resend-otp','UserController@reSendOtp');
        $router->get('thread/list','ThreadsController@threadList');
        $router->get('post/list/{id}','ReplyController@postList');
        $router->post('/user/post-verify-email','UserController@postVerifyEmail');
        $router->post('/user/resend-otp-verify-email','UserController@reSendOtpVerifyEmail');
        $router->get('/thread/{id}','ThreadsController@getThreadById');
        $router->get('get-nick-support-user/{nick_id}','NicknameController@getNickSupportUser');
        $router->post('thread/latest5','ThreadsController@getLatest5Threads');
        // Others Routes

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
        $router->get('user/supports/{id}',[ 'as' => 'user_supports','uses'=>'ProfileController@getUserSupports']);
        $router->post('get-camp-breadcrumb','CampController@getCampBreadCrumb');
        $router->post('get-camp-activity-log','ActivityController@getCampActivityLog');
        $router->post('/get-topic-history','TopicController@getTopicHistory');
        $router->post('/get-camp-history','CampController@getCampHistory');
        $router->post('/parse-camp-statement', 'StatementController@parseStatement');
        $router->post('/support-and-score-count', 'SupportController@getCampSupportAndCount');
        $router->get('/get-terms-and-services-content','TermAndServicesController@getTermAndServicesContent');
        $router->get('/get-privacy-policy-content','PrivacyPolicyController@getPrivacyPolicyContent');
        $router->post('/camp-total-support-score', 'SupportController@getCampTotalSupportScore');
        $router->get('/videos', 'VideoController@getVideos');
        $router->get('/videos/{category}/{categoryId}', 'VideoController@getVideosByCategory');
        $router->post('/notify-if-url-not-exist', 'NotificationController@notifyIfUrlNotExist');
        $router->get('/hot-topic', 'TopicController@hotTopic');
        $router->get('/featured-topic', 'TopicController@featuredTopic');
        $router->post('get-tags-list','TagController@getTagsList');
        $router->post('get-sibling-camps','CampController@getSiblingCamps');
        //$router->post('save-user-tags','ProfileController@saveUserTags');
    });

    //Route Group to access api with user access token
    $router->group(['middleware' => 'auth'], function() use ($router) {
        $router->get('/user/logout','UserController@logoutUser');
        $router->post('change-password','ProfileController@changePassword');
        $router->post('update-profile','ProfileController@updateProfile');

        $router->post('update-profile-picture','ProfileController@updateProfilePicture');
        $router->delete('update-profile-picture','ProfileController@deleteProfilePicture');

        $router->get('user/profile','ProfileController@getProfile');
        $router->post('send-otp','ProfileController@sendOtp');
        $router->post('verify-otp','ProfileController@VerifyOtp');
        $router->post('add-nick-name','NicknameController@addNickName');
        $router->post('update-nick-name/{id}','NicknameController@UpdateNickName');
        $router->get('get-nick-name-list','NicknameController@getNickNameList');
        $router->post('camp/save', ['uses' => 'CampController@store', 'middleware' => 'throttle:1,0.05']);
        $router->post('topic/save', ['uses' => 'TopicController@store', 'middleware' => 'throttle:1,0.05']);
        $router->get('get-direct-supported-camps','SupportController@getDirectSupportedCamps');
        $router->get('get-delegated-supported-camps','SupportController@getDelegatedSupportedCamps');
        $router->post('camp/all-parent','CampController@getAllParentCamp');
        $router->post('camp/get-topic-nickname-used','CampController@getTopicNickNameUsed');
        $router->get('camp/all-about-nickname','CampController@getAllAboutNickName');
        $router->get('/user/social/list','UserController@socialList');
        $router->post('/user/social/social-link', ['uses' => 'UserController@socialLink']);
        $router->delete('/user/social/delete/{id}', ['uses' => 'UserController@socialDelete']);
        $router->post('/user/deactivate', ['uses' => 'UserController@deactivateUser']);
        $router->post('add-folder','UploadController@addFolder');
        $router->post('upload-files','UploadController@uploadFileToS3');
        $router->delete('/folder/delete/{id}', ['uses' => 'UploadController@folderDelete']);
        $router->get('/uploaded-files', 'UploadController@getUploadedFiles');
        $router->post('thread/save', ['uses' => 'ThreadsController@store', 'middleware' => 'throttle:1,0.05']);
        $router->put('thread/update/{id}', ['uses' => 'ThreadsController@update', 'middleware' => 'throttle:1,0.05']);
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
        $router->post('/edit-camp-statement', 'StatementController@editStatement');
        $router->post('/store-camp-statement', 'StatementController@storeStatement');
        $router->post('/post-statement-count', 'StatementController@postStatementCount');
        $router->post('support-order/update','SupportController@updateSupportOrder');
        $router->post('commit/change','TopicController@commitAndNotifyChange');
        $router->post('discard/change','TopicController@discardChange');
        $router->post('/get-statement-comparison','StatementController@getStatementComparison');
        $router->get('support/check','SupportController@checkIfSupportExist');
        $router->post('topic-support-list','SupportController@getSupportInTopic');
        $router->post('agree-to-change','TopicController@agreeToChange');
        $router->post('/manage-camp', 'CampController@manageCamp');
        $router->get('notification-list','NotificationController@notificationList');
        $router->put('notification-is-read/update/{id}','NotificationController@updateIsRead');
        $router->post('notification/read/all/','NotificationController@updateReadAll');
        $router->post('notification/delete/all/','NotificationController@deleteAll');
        $router->post('/edit-camp','CampController@editCampRecord');
        $router->post('/manage-topic','TopicController@manageTopic');
        $router->post('/edit-topic', 'TopicController@editTopicRecord');
        $router->post('/update-fcm-token','NotificationController@updateFcmToken');
        $router->get('/support-reason-list','SupportController@getSupportReason');

        $router->post('/get-change-supporters','SupportController@getChangeSupporters');
        $router->get('/preferred-topic', 'TopicController@preferredTopic');
        $router->post('/create/user/tags', 'TagController@createUserTags');
        $router->post('camp/sign','CampController@signPetition');
        $router->get('camp/sign/check','SupportController@checkIfUserAlreadySignCamp');
        $router->get('/change-email-request','ProfileController@changeEmailRequest');
        $router->post('/emailchange-verify-otp','ProfileController@emailChangeOtpVerification');
        $router->post('/update-email-request','ProfileController@updateEmailRequest');
        $router->post('/update-email','ProfileController@verifyAndUpdateEmail');
        $router->post('/add-email','ProfileController@addEmail');
        $router->get('/users-email','ProfileController@getAllEmail');
    });
    $router->group(['middleware' => 'admin'], function() use ($router) {
        $router->post('/edit-camp-newsfeed','NewsFeedController@editNewsFeed');
        $router->post('/store-camp-newsfeed','NewsFeedController@storeNewsFeed');
        $router->post('/update-camp-newsfeed','NewsFeedController@updateNewsFeed');
        $router->post('/delete-camp-newsfeed','NewsFeedController@deleteNewsFeed');
        $router->post('/login-as-user','UserController@loginAsUser');
    });
    $router->post('/ads','AdsController@getAds');
    $router->post('/images','ImageController@getImages');
    $router->get('/global-search-uploaded-files', 'UploadController@getGlobalSearchUploadedFiles');
    $router->post('/sitemaps', 'SitemapXmlController@index');

    $router->group(['prefix' => 'canonizer/api'], function() use ($router) {
        $router->post('commit/change','TopicController@commitAndNotifyChange');
        $router->post('agree-to-change','TopicController@agreeToChangeForLiveJob');
    });
});
