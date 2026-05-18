<?php
use Illuminate\Support\Facades\Artisan;

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

$router->post('/oauth/token', ['uses' => '\Dusterio\LumenPassport\Http\Controllers\AccessTokenController@issueToken']);


$router->get('/social/twitter/callback',['uses' => 'UserController@twitterCallback']);
$router->post('/social/facebook/delete-data/callback',['uses' => 'UserController@facebookDeleteDataCallBack']);

$router->group(['prefix' => 'api/v3'], function() use ($router)
{
    //Api for non register users
    $router->post('/client-token','UserController@clientToken');

    //EmbeddedCodeController
    $router->post('/embedded-code-tracking','EmbeddedCodeController@createEmbeddedCodeTracking');

    //Search Controller
    $router->get('/search','SearchController@getSearchResults');
    $router->post('/search-filter','SearchController@advanceSearchFilter');
    
    //MetaTag Controller
    $router->post('/meta-tags', 'MetaTagController@getMetaTags');

    //ProfileController
    $router->post('gravatar', 'ProfileController@getGravatar');
    
    $router->post('/user/login',['uses' => 'UserController@loginUser']);
    // $router->post('/user/login',['uses' => 'UserController@loginUser', 'middleware' => 'checkstatus']);
    $router->post('/register','UserController@createUser');

    //Route Group to access api with client token
    $router->group(['middleware' => ['Xss','client']], function() use ($router) 
    {
        $router->post('get-camp-activity-log','ActivityController@getCampActivityLog');
        $router->get('/get-terms-and-services-content','TermAndServicesController@getTermAndServicesContent');
        $router->get('/get-privacy-policy-content','PrivacyPolicyController@getPrivacyPolicyContent');
        $router->get('/get-all-namespaces','NamespaceController@getAll');
        $router->get('/get-whats-new-content','VideoPodcastController@getNewContent');
        $router->get('/get-social-media-links','SocialMediaLinkController@getLinks');
        $router->get('/get-algorithms','AlgorithmController@getAll');
        $router->post('/get-camp-newsfeed','NewsFeedController@getNewsFeed');
        $router->post('/notify-if-url-not-exist', 'NotificationController@notifyIfUrlNotExist');
        $router->post('get-tags-list','TagController@getTagsList');
        $router->get('post/list/{id}','ReplyController@postList');
        $router->get('get-nick-support-user/{nick_id}','NicknameController@getNickSupportUser');

        //UserController
        $router->get('/country/list','UserController@countryList');
        // $router->post('/register','UserController@createUser');
        // $router->post('/user/login',['uses' => 'UserController@loginUser', 'middleware' => 'checkstatus']);
        $router->post('/post-verify-otp','UserController@postVerifyOtp');
        $router->post('/user/social/login','UserController@socialLogin');
        $router->post('/user/social/callback',['uses'=>'UserController@socialCallback']);
        $router->post('/user/resend-otp','UserController@reSendOtp');
        $router->post('/user/post-verify-email','UserController@postVerifyEmail');
        $router->post('/user/resend-otp-verify-email','UserController@reSendOtpVerifyEmail');

        //ForgotPasswordController
        $router->post('/forgot-password/send-otp','ForgotPasswordController@sendOtp');
        $router->post('/forgot-password/verify-otp','ForgotPasswordController@verifyOtp');
        $router->post('/forgot-password/update','ForgotPasswordController@updatePassword');

        //ThreadsController
        $router->get('thread/list','ThreadsController@threadList');
        $router->post('thread/latest5','ThreadsController@getLatest5Threads');
        $router->get('/thread/{id}','ThreadsController@getThreadById');

        //CampController
        $router->post('/get-camp-record','CampController@getCampRecord');
        $router->post('get-camp-breadcrumb','CampController@getCampBreadCrumb');
        $router->post('/get-camp-history','CampController@getCampHistory');
        $router->post('get-sibling-camps','CampController@getSiblingCamps');

       //StatementController
        $router->post('/get-statement-history','StatementController@getStatementHistory');
        $router->post('/get-camp-statement','StatementController@getStatement');
        $router->post('/parse-camp-statement', 'StatementController@parseStatement');

        //ProfileController
        $router->get('/get-languages','ProfileController@getLanguages');
        $router->get('mobile-carrier','ProfileController@mobileCarrier');
        $router->get('user/profile/{id}','ProfileController@getUserProfile');
        $router->get('user/all-supported-camps/{id}','ProfileController@getUserSupportedCamps');
        $router->get('user/supports/{id}',[ 'as' => 'user_supports','uses'=>'ProfileController@getUserSupports']);

        //TopicController 
        $router->post('/get-topic-history','TopicController@getTopicHistory');
        $router->post('/get-topic-record','TopicController@getTopicRecord');
        $router->get('/hot-topic', 'TopicController@hotTopic');
        $router->get('/featured-topic', 'TopicController@featuredTopic');

        //SupportController
        $router->post('/camp-total-support-score','SupportController@getCampTotalSupportScore');
        $router->post('/support-and-score-count', 'SupportController@getCampSupportAndCount');
        $router->post('/get-change-supporters','SupportController@getChangeSupporters');

        //VideoController
        $router->get('/videos', 'VideoController@getVideos');
        $router->get('/videos/{category}/{categoryId}', 'VideoController@getVideosByCategory');

        //$router->post('save-user-tags','ProfileController@saveUserTags');
    });

    //Route Group to access api with user access token
    $router->group(['middleware' => 'auth'], function() use ($router) 
    {
        $router->post('/create/user/tags', 'TagController@createUserTags');
        $router->post('get-activity-log','ActivityController@getActivityLog');
    
        //UserController
        $router->get('/user/logout','UserController@logoutUser');
        $router->get('/user/social/list','UserController@socialList');
        $router->post('/user/social/social-link', ['uses' => 'UserController@socialLink']);
        $router->delete('/user/social/delete/{id}', ['uses' => 'UserController@socialDelete']);
        $router->post('/user/deactivate', ['uses' => 'UserController@deactivateUser']);

        //ProfileController
        $router->post('change-password','ProfileController@changePassword');
        $router->post('update-profile','ProfileController@updateProfile');
        $router->post('update-profile-picture','ProfileController@updateProfilePicture');
        $router->delete('update-profile-picture','ProfileController@deleteProfilePicture');
        $router->get('user/profile','ProfileController@getProfile');
        $router->post('send-otp','ProfileController@sendOtp');
        $router->post('verify-otp','ProfileController@verifyOtp');
        $router->get('/change-email-request','ProfileController@changeEmailRequest');
        $router->post('/emailchange-verify-otp','ProfileController@emailChangeOtpVerification');
        $router->post('/update-email-request','ProfileController@updateEmailRequest');
        $router->post('/update-email','ProfileController@verifyAndUpdateEmail');
        $router->post('/add-email','ProfileController@addEmail');
        $router->get('/users-email','ProfileController@getAllEmail');

        //NicknameController
        $router->post('add-nick-name','NicknameController@addNickName');
        $router->post('set-default-nick-name','NicknameController@setDefaultNickName');
        $router->post('update-nick-name/{id}','NicknameController@UpdateNickName');
        $router->get('get-nick-name-list','NicknameController@getNickNameList');

        //UploadController
        $router->get('/uploaded-files', 'UploadController@getUploadedFiles');
        $router->get('folder/files/{id}', 'UploadController@getFolderFiles');
        
        $router->post('add-folder','UploadController@addFolder');
        $router->post('upload-files','UploadController@uploadFileToS3');
        $router->delete('/file/delete/{id}', ['uses' => 'UploadController@FileDelete']);
        $router->delete('/folder/delete/{id}', ['uses' => 'UploadController@folderDelete']);
     
        //ThreadsController
        $router->post('thread/save', ['uses' => 'ThreadsController@store', 'middleware' => 'throttle:1,0.05']);
        $router->put('thread/update/{id}', ['uses' => 'ThreadsController@update', 'middleware' => 'throttle:1,0.05']);
        
        //SupportController
        $router->post('support/add', 'SupportController@addDirectSupport');
        $router->post('support/add-delegate', 'SupportController@addDelegateSupport');
        $router->post('support/update','SupportController@removeSupport');
        $router->post('support/remove-delegate','SupportController@removeDelegateSupport');
        $router->post('support-order/update','SupportController@updateSupportOrder');
        $router->post('topic-support-list','SupportController@getSupportInTopic');
        $router->get('support/check','SupportController@checkIfSupportExist');
        $router->get('/support-reason-list','SupportController@getSupportReason');
        $router->get('camp/sign/check','SupportController@checkIfUserAlreadySignCamp');
        $router->get('get-direct-supported-camps','SupportController@getDirectSupportedCamps');
        $router->get('get-delegated-supported-camps','SupportController@getDelegatedSupportedCamps');

        //ReplyController
        $router->post('post/save','ReplyController@store');
        $router->put('post/update/{id}','ReplyController@update');
        $router->delete('post/delete/{id}','ReplyController@deletePost');

        //CampController
        $router->post('camp/subscription','CampController@campSubscription');
        $router->post('camp/save', ['uses' => 'CampController@store', 'middleware' => 'throttle:1,0.05']);
        $router->post('camp/all-parent','CampController@getAllParentCamp');
        $router->post('camp/get-topic-nickname-used','CampController@getTopicNickNameUsed');
        $router->get('camp/all-about-nickname','CampController@getAllAboutNickName');
        $router->get('camp/subscription/list','CampController@campSubscriptionList');
        $router->post('/manage-camp', 'CampController@manageCamp');
        $router->post('/edit-camp','CampController@editCampRecord');
        $router->post('camp/sign','CampController@signPetition');

        //StatementController
        $router->post('/edit-camp-statement', 'StatementController@editStatement');
        $router->post('/store-camp-statement', 'StatementController@storeStatement');
        $router->post('/post-statement-count', 'StatementController@postStatementCount');
        $router->post('/get-statement-comparison','StatementController@getStatementComparison');

        //TopicController
        // $router->post('topic/save', ['uses' => 'TopicController@store', 'middleware' => 'throttle:1,0.05']);
        $router->post('topic/save', ['uses' => 'TopicController@store']);

        $router->post('commit/change','TopicController@commitAndNotifyChange');
        $router->post('discard/change','TopicController@discardChange');
        $router->post('agree-to-change','TopicController@agreeToChange');
        $router->post('/manage-topic','TopicController@manageTopic');
        $router->post('/edit-topic', 'TopicController@editTopicRecord');
        $router->get('/preferred-topic', 'TopicController@preferredTopic');

        //NotificationController
        $router->get('notification-list','NotificationController@notificationList');
        $router->put('notification-is-read/update/{id}','NotificationController@updateIsRead');
        $router->post('notification/read/all/','NotificationController@updateReadAll');
        $router->post('notification/delete/all/','NotificationController@deleteAll');
        $router->post('/update-fcm-token','NotificationController@updateFcmToken');

        //Camp Restrictions
        $router->post('camps/{id}/restrict', 'CampRestrictionController@restrict');
        $router->post('camps/{id}/lift-restriction/{user_id}', 'CampRestrictionController@lift');
        $router->post('camps/{id}/extend-restriction/{user_id}', 'CampRestrictionController@extend');
        $router->get('camps/{id}/restrictions', 'CampRestrictionController@index');
        $router->get('camps/{id}/restriction/logs', 'CampRestrictionController@restrictionLogs');

        //AI Agent Management
        $router->get('ai-agents', 'AiAgentController@list');
        $router->get('ai-agents/{id}', 'AiAgentController@show');
        $router->put('ai-agents/{id}', 'AiAgentController@update');
        $router->delete('ai-agents/{id}', 'AiAgentController@destroy');
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
    $router->get('/check-facebook-delete-data-status','UserController@checkFacebookDataDeletionStatus');
});
