<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CampController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\NicknameController;
use App\Http\Controllers\ThreadsController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\TermAndServicesController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\NamespaceController;
use App\Http\Controllers\VideoPodcastController;
use App\Http\Controllers\SocialMediaLinkController;
use App\Http\Controllers\AlgorithmController;
use App\Http\Controllers\NewsFeedController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\EmbeddedCodeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\MetaTagController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\AdsController;
use App\Http\Controllers\SitemapXmlController;
use App\Http\Controllers\CampRestrictionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Consolidated v1 (from dev-service) and v3 (from main-api)
Route::group(['prefix' => 'v3'], function () {
    
    // Public routes
    Route::post('/client-token', [UserController::class, 'clientToken']);
    Route::post('/embedded-code-tracking', [EmbeddedCodeController::class, 'createEmbeddedCodeTracking']);
    Route::get('/search', [SearchController::class, 'getSearchResults']);
    Route::post('/search-filter', [SearchController::class, 'advanceSearchFilter']);
    Route::post('/meta-tags', [MetaTagController::class, 'getMetaTags']);
    Route::post('/gravatar', [ProfileController::class, 'getGravatar']);
    Route::post('/user/login', [UserController::class, 'loginUser']);
    Route::post('/register', [UserController::class, 'createUser']);

    // Client/Xss Middleware group
    Route::group(['middleware' => ['Xss', 'client']], function () {
        Route::post('get-camp-activity-log', [ActivityController::class, 'getCampActivityLog']);
        Route::get('/get-terms-and-services-content', [TermAndServicesController::class, 'getTermAndServicesContent']);
        Route::get('/get-privacy-policy-content', [PrivacyPolicyController::class, 'getPrivacyPolicyContent']);
        Route::get('/get-all-namespaces', [NamespaceController::class, 'getAll']);
        Route::get('/topic-categories', [\App\Http\Controllers\TopicCategoryController::class, 'index']);
        Route::get('/get-whats-new-content', [VideoPodcastController::class, 'getNewContent']);
        Route::get('/get-social-media-links', [SocialMediaLinkController::class, 'getLinks']);
        Route::get('/get-algorithms', [AlgorithmController::class, 'getAll']);
        Route::post('/get-camp-newsfeed', [NewsFeedController::class, 'getNewsFeed']);
        Route::post('/notify-if-url-not-exist', [NotificationController::class, 'notifyIfUrlNotExist']);
        Route::post('get-tags-list', [TagController::class, 'getTagsList']);
        Route::get('post/list/{id}', [ReplyController::class, 'postList']);
        Route::get('get-nick-support-user/{nick_id}', [NicknameController::class, 'getNickSupportUser']);

        Route::get('/country/list', [UserController::class, 'countryList']);
        Route::post('/post-verify-otp', [UserController::class, 'postVerifyOtp']);
        Route::post('/user/social/login', [UserController::class, 'socialLogin']);
        Route::post('/user/social/callback', [UserController::class, 'socialCallback']);
        Route::post('/user/resend-otp', [UserController::class, 'reSendOtp']);
        Route::post('/user/post-verify-email', [UserController::class, 'postVerifyEmail']);
        Route::post('/user/resend-otp-verify-email', [UserController::class, 'reSendOtpVerifyEmail']);

        Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
        Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
        Route::post('/forgot-password/update', [ForgotPasswordController::class, 'updatePassword']);

        Route::get('thread/list', [ThreadsController::class, 'threadList']);
        Route::post('thread/latest5', [ThreadsController::class, 'getLatest5Threads']);
        Route::get('/thread/{id}', [ThreadsController::class, 'getThreadById']);

        Route::post('/get-camp-record', [CampController::class, 'getCampRecord']);
        Route::post('get-camp-breadcrumb', [CampController::class, 'getCampBreadCrumb']);
        Route::post('/get-camp-history', [CampController::class, 'getCampHistory']);
        Route::post('get-sibling-camps', [CampController::class, 'getSiblingCamps']);

        Route::post('/get-statement-history', [StatementController::class, 'getStatementHistory']);
        Route::post('/get-camp-statement', [StatementController::class, 'getStatement']);
        Route::post('/parse-camp-statement', [StatementController::class, 'parseStatement']);

        Route::get('/get-languages', [ProfileController::class, 'getLanguages']);
        Route::get('mobile-carrier', [ProfileController::class, 'mobileCarrier']);
        Route::get('user/profile/{id}', [ProfileController::class, 'getUserProfile']);
        Route::get('user/all-supported-camps/{id}', [ProfileController::class, 'getUserSupportedCamps']);
        Route::get('user/supports/{id}', [ProfileController::class, 'getUserSupports']);

        Route::post('/get-topic-history', [TopicController::class, 'getTopicHistory']);
        Route::post('/get-topic-record', [TopicController::class, 'getTopicRecord']);
        Route::get('/hot-topic', [TopicController::class, 'hotTopic']);
        Route::get('/featured-topic', [TopicController::class, 'featuredTopic']);

        Route::post('/camp-total-support-score', [SupportController::class, 'getCampTotalSupportScore']);
        Route::post('/support-and-score-count', [SupportController::class, 'getCampSupportAndCount']);
        Route::post('/get-change-supporters', [SupportController::class, 'getChangeSupporters']);

        Route::get('/videos', [VideoController::class, 'getVideos']);
        Route::get('/videos/{category}/{categoryId}', [VideoController::class, 'getVideosByCategory']);
    });

    // Auth Middleware group
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('/create/user/tags', [TagController::class, 'createUserTags']);
        Route::post('get-activity-log', [ActivityController::class, 'getActivityLog']);
        
        Route::get('/user/logout', [UserController::class, 'logoutUser']);
        Route::get('/user/social/list', [UserController::class, 'socialList']);
        Route::post('/user/social/social-link', [UserController::class, 'socialLink']);
        Route::delete('/user/social/delete/{id}', [UserController::class, 'socialDelete']);
        Route::post('/user/deactivate', [UserController::class, 'deactivateUser']);

        Route::post('change-password', [ProfileController::class, 'changePassword']);
        Route::post('update-profile', [ProfileController::class, 'updateProfile']);
        Route::post('update-profile-picture', [ProfileController::class, 'updateProfilePicture']);
        Route::delete('update-profile-picture', [ProfileController::class, 'deleteProfilePicture']);
        Route::get('user/profile', [ProfileController::class, 'getProfile']);
        Route::post('send-otp', [ProfileController::class, 'sendOtp']);
        Route::post('verify-otp', [ProfileController::class, 'verifyOtp']);
        Route::get('/change-email-request', [ProfileController::class, 'changeEmailRequest']);
        Route::post('/emailchange-verify-otp', [ProfileController::class, 'emailChangeOtpVerification']);
        Route::post('/update-email-request', [ProfileController::class, 'updateEmailRequest']);
        Route::post('/update-email', [ProfileController::class, 'verifyAndUpdateEmail']);
        Route::post('/add-email', [ProfileController::class, 'addEmail']);
        Route::get('/users-email', [ProfileController::class, 'getAllEmail']);

        Route::post('add-nick-name', [NicknameController::class, 'addNickName']);
        Route::post('set-default-nick-name', [NicknameController::class, 'setDefaultNickName']);
        Route::post('update-nick-name/{id}', [NicknameController::class, 'UpdateNickName']);
        Route::get('get-nick-name-list', [NicknameController::class, 'getNickNameList']);

        Route::get('/uploaded-files', [UploadController::class, 'getUploadedFiles']);
        Route::get('folder/files/{id}', [UploadController::class, 'getFolderFiles']);
        Route::post('add-folder', [UploadController::class, 'addFolder']);
        Route::post('upload-files', [UploadController::class, 'uploadFileToS3']);
        Route::delete('/file/delete/{id}', [UploadController::class, 'FileDelete']);
        Route::delete('/folder/delete/{id}', [UploadController::class, 'folderDelete']);

        Route::post('thread/save', [ThreadsController::class, 'store']);
        Route::put('thread/update/{id}', [ThreadsController::class, 'update']);

        Route::post('support/add', [SupportController::class, 'addDirectSupport']);
        Route::post('support/add-delegate', [SupportController::class, 'addDelegateSupport']);
        Route::post('support/update', [SupportController::class, 'removeSupport']);
        Route::post('support/remove-delegate', [SupportController::class, 'removeDelegateSupport']);
        Route::post('support-order/update', [SupportController::class, 'updateSupportOrder']);
        Route::post('topic-support-list', [SupportController::class, 'getSupportInTopic']);
        Route::get('support/check', [SupportController::class, 'checkIfSupportExist']);
        Route::get('/support-reason-list', [SupportController::class, 'getSupportReason']);
        Route::get('camp/sign/check', [SupportController::class, 'checkIfUserAlreadySignCamp']);
        Route::get('get-direct-supported-camps', [SupportController::class, 'getDirectSupportedCamps']);
        Route::get('get-delegated-supported-camps', [SupportController::class, 'getDelegatedSupportedCamps']);

        Route::post('post/save', [ReplyController::class, 'store']);
        Route::put('post/update/{id}', [ReplyController::class, 'update']);
        Route::delete('post/delete/{id}', [ReplyController::class, 'deletePost']);

        Route::post('camp/subscription', [CampController::class, 'campSubscription']);
        Route::post('camp/save', [CampController::class, 'store']);
        Route::post('camp/all-parent', [CampController::class, 'getAllParentCamp']);
        Route::post('camp/get-topic-nickname-used', [CampController::class, 'getTopicNickNameUsed']);
        Route::get('camp/all-about-nickname', [CampController::class, 'getAllAboutNickName']);
        Route::get('camp/subscription/list', [CampController::class, 'campSubscriptionList']);
        Route::post('/manage-camp', [CampController::class, 'manageCamp']);
        Route::post('/edit-camp', [CampController::class, 'editCampRecord']);
        Route::post('camp/sign', [CampController::class, 'signPetition']);

        Route::post('/edit-camp-statement', [StatementController::class, 'editStatement']);
        Route::post('/store-camp-statement', [StatementController::class, 'storeStatement']);
        Route::post('/post-statement-count', [StatementController::class, 'postStatementCount']);
        Route::post('/get-statement-comparison', [StatementController::class, 'getStatementComparison']);

        Route::post('topic/save', [TopicController::class, 'store']);
        Route::post('commit/change', [TopicController::class, 'commitAndNotifyChange']);
        Route::post('discard/change', [TopicController::class, 'discardChange']);
        Route::post('agree-to-change', [TopicController::class, 'agreeToChange']);
        Route::post('/manage-topic', [TopicController::class, 'manageTopic']);
        Route::post('/edit-topic', [TopicController::class, 'editTopicRecord']);
        Route::get('/preferred-topic', [TopicController::class, 'preferredTopic']);

        Route::get('notification-list', [NotificationController::class, 'notificationList']);
        Route::put('notification-is-read/update/{id}', [NotificationController::class, 'updateIsRead']);
        Route::post('notification/read/all/', [NotificationController::class, 'updateReadAll']);
        Route::post('notification/delete/all/', [NotificationController::class, 'deleteAll']);
        Route::post('/update-fcm-token', [NotificationController::class, 'updateFcmToken']);

        Route::post('camps/{id}/restrict', [CampRestrictionController::class, 'restrict']);
        Route::post('camps/{id}/lift-restriction/{user_id}', [CampRestrictionController::class, 'lift']);
        Route::post('camps/{id}/extend-restriction/{user_id}', [CampRestrictionController::class, 'extend']);
        Route::get('camps/{id}/restrictions', [CampRestrictionController::class, 'index']);
        Route::get('camps/{id}/restriction/logs', [CampRestrictionController::class, 'restrictionLogs']);
    });

    // Admin Middleware group
    Route::group(['middleware' => 'admin'], function () {
        Route::post('/edit-camp-newsfeed', [NewsFeedController::class, 'editNewsFeed']);
        Route::post('/store-camp-newsfeed', [NewsFeedController::class, 'storeNewsFeed']);
        Route::post('/update-camp-newsfeed', [NewsFeedController::class, 'updateNewsFeed']);
        Route::post('/delete-camp-newsfeed', [NewsFeedController::class, 'deleteNewsFeed']);
        Route::post('/login-as-user', [UserController::class, 'loginAsUser']);
        Route::post('/topic-category/assign', [\App\Http\Controllers\TopicCategoryController::class, 'assign']);
        Route::get('/admin/topics', [\App\Http\Controllers\TopicCategoryController::class, 'adminTopicList']);
    });

    Route::post('/ads', [AdsController::class, 'getAds']);
    Route::post('/images', [ImageController::class, 'getImages']);
    Route::get('/global-search-uploaded-files', [UploadController::class, 'getGlobalSearchUploadedFiles']);
    Route::post('/sitemaps', [SitemapXmlController::class, 'index']);

    Route::group(['prefix' => 'canonizer/api'], function () {
        Route::post('commit/change', [TopicController::class, 'commitAndNotifyChange']);
        Route::post('agree-to-change', [TopicController::class, 'agreeToChangeForLiveJob']);
    });
    Route::get('/check-facebook-delete-data-status', [UserController::class, 'checkFacebookDataDeletionStatus']);
});

// Merger v1 routes (from dev-service)
Route::group(['prefix' => 'v1'], function () {
    // Trees
    Route::group(['prefix' => 'tree'], function () {
        Route::post('/store', [\App\Http\Controllers\Api\v1\TreeController::class, 'store'])->middleware('auth:api');
        Route::post('/remove-sandbox-tree', [\App\Http\Controllers\Api\v1\TopicController::class, 'removeCacheSpecificTopics'])->middleware('auth:api');
        Route::post('/get', [\App\Http\Controllers\Api\v1\TreeController::class, 'find']);
        Route::get('/all', function () {
            \Illuminate\Support\Facades\Artisan::call('tree:all');
            return response()->json(['message' => 'All topic trees generated successfully.']);
        });
    });

    // Topics
    Route::group(['prefix' => 'topic'], function () {
        Route::post('/getAll', [\App\Http\Controllers\Api\v1\TopicController::class, 'getAll']);
    });

    // Timelines
    Route::group(['prefix' => 'timeline'], function () {
        Route::post('/store', [\App\Http\Controllers\Api\v1\TimelineController::class, 'store'])->middleware('auth:api');
        Route::post('/get', [\App\Http\Controllers\Api\v1\TimelineController::class, 'find']);
        Route::get('/all', function () {
            \Illuminate\Support\Facades\Artisan::call('timeline:all');
            return response()->json(['message' => 'All topic timelines generated successfully.']);
        });
        Route::get('/adding-specific-topic/{topic_num}/{algorithm_id}', function (string $topic_num = null, string $algorithm_id = null) {
            \Illuminate\Support\Facades\Artisan::call("timeline:all $topic_num $algorithm_id");
            return response()->json(['message' => 'Specific topic timelines generated successfully.']);
        });
    });
});
