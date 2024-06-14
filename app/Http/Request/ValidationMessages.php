<?php

namespace App\Http\Request;

class ValidationMessages
{
    public function getTokenValidationMessages(): array
    {
        return ([
            'client_id.required' => trans('message.verify_otp.client_id_required'),
            'client_secret.required' => trans('message.login.client_secret_required'),
        ]);
    }

    public function getLoginValidationMessages(): array
    {
        return ([
            'username.required' => trans('message.login.username_required'),
            'password.required' => trans('message.login.password_required'),
            'client_id.required' => trans('message.verify_otp.client_id_required'),
            'client_secret.required' => trans('message.login.client_secret_required'),
        ]);
    }

    public function getRegistrationValidationMessages(): array
    {
        return ([
            'first_name.regex' => trans('message.validation_registration.first_name_regex'),
            'first_name.required' => trans('message.validation_registration.first_name_required'),
            'first_name.string' => trans('message.validation_registration.first_name_string'),
            'first_name.max' => trans('message.validation_registration.first_name_max'),

            'middle_name.regex' => trans('message.validation_registration.middle_name_regex'),
            'middle_name.required' => trans('message.validation_registration.middle_name_required'),
            'middle_name.string' => trans('message.validation_registration.middle_name_string'),
            'middle_name.max' => trans('message.validation_registration.middle_name_max'),

            'last_name.regex' => trans('message.validation_registration.last_name_regex'),
            'last_name.required' => trans('message.validation_registration.last_name_required'),
            'last_name.string' => trans('message.validation_registration.last_name_string'),
            'last_name.max' => trans('message.validation_registration.last_name_max'),

            'email.required' => trans('message.validation_registration.email_required'),
            'email.string' => trans('message.validation_registration.email_string'),
            'email.email' => trans('message.validation_registration.email_email'),
            'email.max' => trans('message.validation_registration.email_max'),
            'email.unique' => trans('message.validation_registration.email_unique'),

            'password.required' => trans('message.validation_registration.password_required'),
            'password.regex' => trans('message.validation_registration.password_regex'),
            
            'password_confirmation.required' => trans('message.validation_registration.password_confirmation_required'),
            'password_confirmation.same' => trans('message.validation_registration.password_confirmation_same'),
            
            'phone_number.unique' => trans('message.validation_registration.phone_number_unique'),

            'country_code.required' => trans('message.validation_registration.country_code_required'),
        ]);
    }

    public function getVerifyOtpValidationMessages(): array
    {
        return ([
            'otp.required' => trans('message.verify_otp.otp_required'),
            'username.required' => trans('message.verify_otp.username_required'),
            'client_id.required' => trans('message.verify_otp.client_id_required'),
            'client_secret.required' => trans('message.verify_otp.client_secret_required'),
        ]);
    }

    public function getSocialLoginValidationMessages(): array
    {
        return ([
            'provider.required' => trans('message.social.provider_required')
        ]);
    }

    public function getSocialCallbackValidationMessages(): array
    {
        return ([
            'client_id.required' => trans('message.social.client_id_required'),
            'client_secret.required' => trans('message.social.client_secret_required'),
            'provider.required' => trans('message.social.provider_required'),
            'code.required' => trans('message.social.code_required'),
        ]);
    }

    public function getChangePasswordValidationMessages(): array
    {
        return ([
            'new_password.regex' => trans('message.validation_change_password.new_password_regex'),
            'new_password.required' => trans('message.validation_change_password.new_password_required'),
            'current_password.required' => trans('message.validation_change_password.current_password_required'),
            'confirm_password.same' => trans('message.validation_change_password.confirm_password_match'),
            'confirm_password.required' => trans('message.validation_change_password.confirm_password_required'),
            'new_password.different' => trans('message.validation_change_password.new_password_different'),

        ]);
    }

    public function getUpdateProfileValidationMessages(): array
    {
        return ([
            'first_name.required' => trans('message.validation_update_profile.first_name_required'),
            'first_name.regex' => trans('message.validation_update_profile.first_name_regex'),
            'last_name.required' => trans('message.validation_update_profile.last_name_required'),
            'last_name.regex' => trans('message.validation_update_profile.last_name_regex'),
            'middle_name.regex' => trans('message.validation_update_profile.middle_name_regex'),
            'city.regex' => trans('message.validation_update_profile.city_regex'),
            'state.regex' => trans('message.validation_update_profile.state_regex'),
            'country.regex' => trans('message.validation_update_profile.country_regex'),
            'postal_code.regex' => trans('message.validation_update_profile.postal_code_regex'),
            'phone_number.digits' => trans('message.phone_number.valid_digits')
        ]);
    }

    public function getUpdateProfilePictureValidationMessages(): array
    {
        return ([
            'profile_picture.required' => trans('message.validation_update_profile.profile_picture_required'),
            'profile_picture.file' => trans('message.validation_update_profile.profile_picture_file'),
            'profile_picture.mimetypes' => trans('message.validation_update_profile.profile_picture_mimes'),
            'profile_picture.max' => trans('message.validation_update_profile.profile_picture_size'),
            'is_update.boolean' => trans("message.validation_update_profile.profile_pic_update_flag")
        ]);
    }

    public function getForgotPasswordSendOtpValidationMessages(): array
    {
        return ([
            'email' => trans('message.validation_forgot_password.email_required'),
            'email.required' => trans('message.validation_forgot_password.email_required'),
            'email.regex' => trans('message.validation_forgot_password.email_required'),
            'email.max' => trans('message.validation_forgot_password.email_max'),
        ]);
    }

    public function getForgotPasswordVerifyOtpValidationMessages(): array
    {
        return ([
            'otp.required' => trans('message.validation_forgot_password.otp_required'),
            'username.required' => trans('message.validation_forgot_password.email_required'),
        ]);
    }

    public function getForgotPasswordUpdateValidationMessages(): array
    {
        return ([
            'username.required' => trans('message.validation_forgot_password.username_required'),
            'new_password.regex' => trans('message.validation_forgot_password.new_password_regex'),
            'current_password.required' => trans('message.validation_forgot_password.current_password_required'),
        ]);
    }

    public function getUserReSendOtpValidationMessages(): array
    {
        return ([
            'email' => trans('message.error.email_invalid'),
            'email.required' => trans('message.reSendOTP.email_required'),
        ]);
    }

    public function getStatementValidationMessages(): array
    {
        return [

            'as_of.in' => trans('message.validation_get_statement.as_of_in'),
            'as_of_date.required_if' => trans('message.validation_get_statement.as_of_date_required'),
            'topic_num.required' => trans('message.validation_get_statement.topic_num_required'),
            'topic_num.exists' => trans('message.error.camp_live_statement_not_found'),
            'camp_num.required' => trans('message.validation_get_statement.camp_num_required')
        ];
    }

    public function getNewsFeedValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_get_newsfeed.topic_num_required'),
            'camp_num.required' => trans('message.validation_get_newsfeed.camp_num_required')
        ];
    }

    public function getAdsValidationMessages(): array
    {
        return [
            'page_name.required' => trans('message.validation_get_ads.page_name_required'),
            'page_name.string' => trans('message.validation_get_ads.page_name_string')
        ];
    }

    public function getImageValidationMessages(): array
    {
        return [
            'page_name.required' => trans('message.validation_get_image.page_name_required'),
            'page_name.string' => trans('message.validation_get_image.page_name_string')
        ];
    }
    public function getNewsFeedUpdateValidationMessages(): array
    {
        return ([
            'display_text.required' => trans('message.validation_update_newsfeed.display_text_required'),
            "display_text.max" => trans('message.validation_update_newsfeed.display_text_max'),
            "link.regex" => trans('message.validation_update_newsfeed.link_regex'),
            "link.required" => trans('message.validation_update_newsfeed.link_required'),
            "available_for_child.boolean" => trans('message.validation_update_newsfeed.available_for_child_boolean'),
            'available_for_child.required' => trans('message.validation_update_newsfeed.available_for_child_required'),
            'newsfeed_id.required' => trans('message.validation_update_newsfeed.newsfeed_id_required'),
            'newsfeed_id.exists' => trans('message.validation_update_newsfeed.not_found'),
            'submitter_nick_id.required'  => trans('message.validation_update_newsfeed.submitter_nick_id_required'),
        ]);
    }

    public function getCampStoreValidationMessages(): array
    {
        return ([
            'camp_name.regex' => trans('message.validation_camp_store.camp_name_regex'),
            'nick_name.required' => trans('message.validation_camp_store.nick_name_required'),
            'camp_name.required' => trans('message.validation_camp_store.camp_name_required'),
            'camp_name.max' => trans('message.validation_camp_store.camp_name_max'),
            'camp_name.unique' => trans('message.validation_camp_store.camp_name_unique'),
            'camp_about_url.max' => trans('message.validation_camp_store.camp_about_url_max'),
            'camp_about_url.regex' => trans('message.validation_camp_store.camp_about_url_regex'),
            'objection.required' => trans('message.validation_camp_store.objection_required'),
            'objection_reason.max' => trans('message.validation_camp_store.objection_reason_max'),
            'asof.in' => trans('message.validation_camp_store.asof_in')
        ]);
    }

    public function getTopicStoreValidationMessages(): array
    {
        return ([
            'topic_name.required' => trans('message.validation_topic_store.topic_name_required'),
            'topic_name.max' => trans('message.validation_topic_store.topic_name_max'),
            'topic_name.regex' => trans('message.validation_topic_store.topic_name_regex'),
            'topic_name.unique' => trans('message.validation_topic_store.topic_name_unique'),
            'namespace.required' => trans('message.validation_topic_store.namespace_required'),
            'create_namespace.required_if' => trans('message.validation_topic_store.create_namespace_required_if'),
            'create_namespace.max' => trans('message.validation_topic_store.create_namespace_max'),
            'nick_name.required' => trans('message.validation_topic_store.nick_name_required'),
            'objection_reason.required' => trans('message.validation_topic_store.objection_reason_required'),
            'objection_reason.max' => trans('message.validation_topic_store.objection_reason_max'),
            'asof.in' => trans('message.validation_topic_store.asof_in')
        ]);
    }

    public function getNewsFeedDeleteValidationMessages(): array
    {
        return [
            'newsfeed_id.required' => trans('message.validation_delete_newsfeed.newsfeed_id_required'),
            'newsfeed_id.exists' => trans('message.validation_delete_newsfeed.not_found')
        ];
    }

    public function getNewsFeedEditValidationMessages(): array
    {
        return [
            'newsfeed_id.required' => trans('message.validation_edit_newsfeed.newsfeed_id_required'),
            'newsfeed_id.exists' => trans('message.validation_edit_newsfeed.not_found')
        ];
    }

    public function getAllParentCampValidationMessages(): array
    {
        return ([
            'topic_num.required' => trans('message.get_all_parent_camp.topic_num_required'),
        ]);
    }

    public function getVerifyPhoneValidationMessages(): array
    {
        return ([
            'phone_number.required' => trans('message.phone_number.required'),
            'phone_number.digits' => trans('message.phone_number.valid_digits'),
            'mobile_carrier.required' => trans('message.phone_number.mobile_carrier_required')

        ]);
    }

    public function getVerifyOtpValidatonMessages(): array
    {
        return ([
            'otp.required' => trans('message.otp.required'),
            'otp.digits' =>  trans('message.otp.valid_digits')
        ]);
    }

    public function getCampRecordValidationMessages(): array
    {
        return [
            'as_of.in' => trans('message.validation_get_camp_record.as_of_in'),
            'as_of_date.required_if' => trans('message.validation_get_camp_record.as_of_date_required'),
            'topic_num.required' => trans('message.validation_get_camp_record.topic_num_required'),
            'camp_num.required' => trans('message.validation_get_camp_record.camp_num_required'),
            'topic_num.numeric' => trans('message.validation_get_camp_record.topic_num_numeric'),
            'camp_num.numeric' => trans('message.validation_get_camp_record.camp_num_numeric')
        ];
    }

    public function getTopicRecordValidationMessages(): array
    {
        return [
            'as_of.in' => trans('message.validation_get_topic_record.as_of_in'),
            'as_of_date.required_if' => trans('message.validation_get_topic_record.as_of_date_required'),
            'topic_num.required' => trans('message.validation_get_topic_record.topic_num_required'),
            'camp_num.required' => trans('message.validation_get_topic_record.camp_num_required')
        ];
    }

    public function getDeactivateUserValidationMessages(): array
    {
        return ([
            'user_id.required' => trans('message.social.user_id_required'),
        ]);
    }


    public function getUploadFileValidationMessages(): array
    {
        return ([
            'file.required' => trans('message.uploads.image_required'),
            //'file.*.mimes' => trans('message.uploads.image_mimes'),
            'file.*.max' => trans('message.uploads.image_size'),
            'name.*.required' => trans('message.uploads.image_name_required'),
            'name.*.unique' => trans('message.uploads.image_name_unique'),

        ]);
    }

    public function getNewsFeedStoreValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_store_newsfeed.topic_num_required'),
            'camp_num.required' => trans('message.validation_store_newsfeed.camp_num_required'),
            'link.required' => trans('message.validation_store_newsfeed.link_required'),
            'link.regex' => trans('message.validation_store_newsfeed.link_regix'),
            'available_for_child.required' => trans('message.validation_store_newsfeed.available_for_child_required'),
            'display_text.required' => trans('message.validation_store_newsfeed.display_text_required'),
            "display_text.max" => trans('message.validation_store_newsfeed.display_text_max'),
            'submitter_nick_id.required' => trans('message.validation_store_newsfeed.submitter_nick_id_required'),
        ];
    }

    public function getThreadStoreValidationMessages(): array
    {
        return [
            'title.regex' => trans('message.thread.title_regex'),
            'title.required' => trans('message.thread.title_required'),
            'title.max' => trans('message.thread.title_max'),
            'nick_name.required' => trans('message.thread.nick_name_required'),
            'camp_num.required' => trans('message.thread.camp_num_required'),
            'topic_num.required' => trans('message.thread.topic_num_required'),
            'topic_name.required' => trans('message.thread.topic_name_required'),
        ];
    }

    public function getThreadListValidationMessages(): array
    {
        return [
            'camp_num.required' => trans('message.thread.camp_num_required'),
            'topic_num.required' => trans('message.thread.topic_num_required'),
            'type.required' => trans('message.thread.type_required'),
        ];
    }

    public function getThreadUpdateValidationMessages(): array
    {
        return [
            'title.regex' => trans('message.thread.title_regex'),
            'title.required' => trans('message.thread.title_required'),
            'title.max' => trans('message.thread.title_max'),
            'camp_num.required' => trans('message.thread.camp_num_required'),
            'topic_num.required' => trans('message.thread.topic_num_required'),
        ];
    }

    public function getStatementHistoryValidationMessages(): array
    {
        return ([
            'topic_num.required' => trans('message.validation_get_statementHistory.topic_num_required'),
            'camp_num.required' => trans('message.validation_get_statementHistory.camp_num_required'),
            'topic_num.numeric' => trans('message.validation_get_statementHistory.topic_num_numeric'),
            'camp_num.numeric' => trans('message.validation_get_statementHistory.camp_num_numeric'),
            'type.in' => trans('message.validation_get_statementHistory.type_in'),
            'as_of.in' => trans('message.validation_get_statementHistory.as_of_in'),
            'as_of_date.required_if' => trans('message.validation_get_statementHistory.as_of_date_required_if'),
            'per_page.required' => trans('message.validation_get_statementHistory.per_page_required'),
            'page.required' => trans('message.validation_get_statementHistory.page_required'),
        ]);
    }

    public function getAddDirectSupportMessages(): array
    {
        return ([
            'nick_name_id.required' => trans('message.support_validation.nick_name_required'),
            'camps.required' => trans('message.support_validation.camps_required'),
            'camps.min' => trans('message.support_validation.camps_required'),
            'topic_num.required' => trans('message.support_validation.topic_num_required'),
            'topic_num.integer' => trans('message.support_validation.topic_num_required'),
            'camps.*.camp_num.required' => trans('message.support_validation.camp_num_required'),
            'camps.*.support_order.required' => trans('message.support_validation.support_order_required'),
            'camps.*.camp_num.integer' => trans('message.support_validation.camp_num_invalid'),
            'camps.*.support_order.integer' => trans('message.support_validation.support_order_invalid'),

        ]);
    }

    public function getAddDelegateSupportMessages(): array
    {
        return ([
            'nick_name_id.required' => trans('message.delegate_support_validation.nick_name_required'),
            'delegated_nick_name_id.required' => trans('message.delegate_support_validation.delegate_nick_name_required'),
            'topic_num.required' => trans('message.delegate_support_validation.topic_num_required'),

        ]);
    }

    public function getPostStoreValidationMessages(): array
    {
        return [
            'body.regex' => trans('message.post.body_regex'),
            'body.required' => trans('message.post.body_regex'),
            'nick_name.required' => trans('message.post.nick_name_required'),
            'camp_num.required' => trans('message.post.camp_num_required'),
            'topic_num.required' => trans('message.post.topic_num_required'),
            'topic_name.required' => trans('message.post.topic_name_required'),
            'thread_id.required' => trans('message.post.thread_id_required'),
        ];
    }

    public function getPostUpdateValidationMessages(): array
    {
        return [
            'body.regex' => trans('message.post.body_regex'),
            'body.required' => trans('message.post.body_regex'),
            'camp_num.required' => trans('message.post.camp_num_required'),
            'topic_num.required' => trans('message.post.topic_num_required'),
            'topic_name.required' => trans('message.post.topic_name_required'),
            'thread_id.required' => trans('message.post.thread_id_required'),
        ];
    }

    public function getAllCampSubscriptionValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_subscription_camp.topic_num_required'),
            'camp_num.required' => trans('message.validation_subscription_camp.camp_num_required'),
            "checked.boolean" => trans('message.validation_subscription_camp.checked_boolean'),
            'checked.required' => trans('message.validation_subscription_camp.checked_required'),
            'subscription_id.required_if' => trans('message.validation_subscription_camp.subscription_id_required'),
            'subscription_id.exists' => trans('message.validation_subscription_camp.subscription_id_not_found'),

        ];
    }

    public function getActivityLogValidationMessages(): array
    {
        return [
            'per_page.required' => trans('message.validation_get_activity_log.per_page_required'),
            'page.required' => trans('message.validation_get_activity_log.page_required'),
            'log_type.required' => trans('message.validation_get_activity_log.log_type_required'),
            'log_type.in' => trans('message.validation_get_activity_log.log_type_in')
        ];
    }

    public function getStatementStoreValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_store_statement.topic_num_required'),
            'camp_num.required' => trans('message.validation_store_statement.camp_num_required'),
            'statement.required' => trans('message.validation_store_statement.statement_required'),
            'nick_name.required' => trans('message.validation_store_statement.nick_name_required'),
            'submitter.required' => trans('message.validation_store_statement.submitter_required'),
            'event_type.required' => trans('message.validation_store_statement.event_type_required'),
            'event_type.in' => trans('message.validation_store_statement.event_type_in'),
            'parent_camp_num.required' => trans('message.validation_store_statement.parent_camp_num_required'),
            'statement_id.required_if' => trans('message.validation_store_statement.statement_id_required_if'),
            'objection_reason.required_if' => trans('message.validation_store_statement.objection_reason_required_if'),
        ];
    }

    public function getPostVerifyEmailValidationMessages(): array
    {
        return ([
            'client_id.required' => trans('message.social.client_id_required'),
            'client_secret.required' => trans('message.social.client_secret_required'),
            'provider.required' => trans('message.social.provider_required'),
            'code.required' => trans('message.social.code_required'),
            'email' => trans('message.validation_forgot_password.email_required'),
            'email.required' => trans('message.validation_forgot_password.email_required'),
            'email.regex' => trans('message.validation_forgot_password.email_required'),
            'email.max' => trans('message.validation_forgot_password.email_max'),
            'otp.required' => trans('message.validation_forgot_password.otp_required'),
        ]);
    }

    public function getReSendOtpVerifyEmailValidationMessages(): array
    {
        return ([
            'client_id.required' => trans('message.social.client_id_required'),
            'client_secret.required' => trans('message.social.client_secret_required'),
            'provider.required' => trans('message.social.provider_required'),
            'code.required' => trans('message.social.code_required'),
            'email' => trans('message.validation_forgot_password.email_required'),
            'email.required' => trans('message.validation_forgot_password.email_required'),
            'email.regex' => trans('message.validation_forgot_password.email_required'),
            'email.max' => trans('message.validation_forgot_password.email_max'),
        ]);
    }

    public function getCampBreadCrumbValidationMessages(): array
    {
        return [
            'as_of.in' => trans('message.validation_get_camp_bread_crumb.as_of_in'),
            'as_of_date.required_if' => trans('message.validation_get_camp_bread_crumb.as_of_date_required'),
            'topic_num.required' => trans('message.validation_get_camp_bread_crumb.topic_num_required'),
            'camp_num.required' => trans('message.validation_get_camp_bread_crumb.camp_num_required')
        ];
    }

    public function getCommitChangeValidationMessages(): array
    {
        return [
            'type.in' => trans('message.validation_commit_change.type_in'),
            'id.required' => trans('message.validation_commit_change.id_required'),
        ];
    }

    public function getDiscardChangeValidationMessages(): array
    {
        return [
            'id.required' => trans('message.validation_discard_change.id_required'),
            'type.required' => trans('message.validation_discard_change.type_required'),
            'type.in' => trans('message.validation_discard_change.type_in'),
        ];
    }
    
    public function getStatementComparisonValidationMessages(): array
    {
        return [
            'ids.required' => trans('message.statement_comparison.ids_required'),
            'topic_num.required' => trans('message.statement_comparison.topic_num_required'),
            'camp_num.required' => trans('message.statement_comparison.camp_num_required'),
        ];
    }

    public function getCampActivityLogValidationMessages(): array
    {
        return [
            'camp_num.required' => trans('message.validation_get_topic_activity_log.camp_num_required'),
            'camp_num.integer' => trans('message.validation_get_topic_activity_log.camp_num_integer'),
            'topic_num.required' => trans('message.validation_get_topic_activity_log.topic_num_required'),
            'topic_num.integer' => trans('message.validation_get_topic_activity_log.topic_num_integer'),
        ];
    }

    public function getAgreeToChangeValidationMessages(): array
    {
        return [
            'record_id.required' => trans('message.validation_agree_to_change.record_id_required'),
            'user_agreed.required' => trans('message.validation_agree_to_change.user_agreed_required'),
            'user_agreed.boolean' => trans('message.validation_agree_to_change.user_agreed_boolean'),
            'topic_num.required' => trans('message.validation_agree_to_change.topic_num_required'),
            'camp_num.required' => trans('message.validation_agree_to_change.camp_num_required'),
            'change_for.in' => trans('message.validation_agree_to_change.change_for_in'),
            'change_for.required' => trans('message.validation_agree_to_change.change_for_required'),
            'nick_name_id.required' => trans('message.validation_agree_to_change.nick_name_id_required'),
        ];
    }

    public function getAgreeToChangeForLiveJobValidationMessages(): array
    {
        return [
            'record_id.required' => trans('message.validation_agree_to_change.record_id_required'),
            'topic_num.required' => trans('message.validation_agree_to_change.topic_num_required'),
            'camp_num.required' => trans('message.validation_agree_to_change.camp_num_required'),
            'change_for.in' => trans('message.validation_agree_to_change.change_for_in'),
            'change_for.required' => trans('message.validation_agree_to_change.change_for_required'),
        ];
    }

    public function getTopicHistoryValidationMessages(): array
    {
        return ([
            'topic_num.required' => trans('message.validation_get_topic_history.topic_num_required'),
            'topic_num.numeric' => trans('message.validation_get_topic_history.topic_num_numeric'),
            'type.in' => trans('message.validation_get_topic_history.type_in'),
            'per_page.required' => trans('message.validation_get_topic_history.per_page_required'),
            'page.required' => trans('message.validation_get_topic_history.page_required'),
        ]);
    }

    public function getCampHistoryValidationMessages(): array
    {
        return ([
            'topic_num.required' => trans('message.validation_get_camp_history.topic_num_required'),
            'camp_num.required' => trans('message.validation_get_camp_history.camp_num_required'),
            'topic_num.numeric' => trans('message.validation_get_camp_history.topic_num_numeric'),
            'camp_num.numeric' => trans('message.validation_get_camp_history.camp_num_numeric'),
            'type.in' => trans('message.validation_get_camp_history.type_in'),
            'per_page.required' => trans('message.validation_get_camp_history.per_page_required'),
            'page.required' => trans('message.validation_get_camp_history.page_required'),
        ]);
    }
    
    public function getManageCampValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_manage_camp.topic_num_required'),
            'camp_num.required' => trans('message.validation_manage_camp.camp_num_required'),
            'camp_id.required' => trans('message.validation_manage_camp.camp_id_required'),
            'nick_name.required' => trans('message.validation_manage_camp.nick_name_required'),
            'submitter.required' => trans('message.validation_manage_camp.submitter_required'),
            'event_type.required' => trans('message.validation_manage_camp.event_type_required'),
            'event_type.in' => trans('message.validation_manage_camp.event_type_in'),
            'camp_name.required' => trans('message.validation_manage_camp.camp_name_required'),
            'camp_about_url.regex' => trans('message.validation_manage_camp.camp_about_url_regex'),
            'objection_reason.required_if' => trans('message.validation_manage_camp.objection_reason_required_if'),
        ];
    }

    public function checkCampStatusValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_check_camp_status.topic_num_required'),
            'topic_num.integer' => trans('message.validation_check_camp_status.topic_num_integer'),
            'topic_num.exists' => trans('message.validation_check_camp_status.topic_num_exists'),
            'topic_num.max' => [
                'numeric' => trans('message.validation_check_camp_status.topic_num_max_numeric')
            ],
            'camp_num.required' => trans('message.validation_check_camp_status.camp_num_required'),
            'camp_num.integer' => trans('message.validation_check_camp_status.camp_num_integer'),
            'camp_num.max' => [
                'numeric' => trans('message.validation_check_camp_status.camp_num_max_numeric')
            ],
        ];
    }

    public function getManageTopicValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_manage_topic.topic_num_required'),
            'topic_id.required' => trans('message.validation_manage_topic.topic_id_required'),
            'nick_name.required' => trans('message.validation_manage_topic.nick_name_required'),
            'submitter.required' => trans('message.validation_manage_topic.submitter_required'),
            'event_type.required' => trans('message.validation_manage_topic.event_type_required'),
            'event_type.in' => trans('message.validation_manage_topic.event_type_in'),
            'objection_reason.required_if' => trans('message.validation_manage_topic.objection_reason_required_if'),
        ];
    }

    public function getFcmTokenValidationMessages(): array
    {
        return ([
            'fcm_token.required' => trans('message.notification_message.fcm_token_required'),
        ]);
    }

    public function getParseStatementValidationMessages(): array
    {
        return [
            'value.required' => trans('message.validation_parse_statement.value_required')
        ];
    }

    public function getEditCaseValidationMessages(): array
    {
        return [
            'record_id.required' => trans('message.validation_edit.record_id_required'),
            'event_type.required' => trans('message.validation_edit.event_type_required')
        ];
    }

    public function getMetaTagsValidationMessages(): array
    {
        return [
            'page_name.required' => trans('message.validation_meta_tags.page_name_required'),
            'page_name.string' => trans('message.validation_meta_tags.page_name_string'),
            'page_name.alpha' => trans('message.validation_meta_tags.page_name_alpha'),

            'keys.topic_num.required' => trans('message.validation_meta_tags.topic_num_required'),
            'keys.topic_num.numeric' => trans('message.validation_meta_tags.topic_num_numeric'),
            'keys.topic_num.gt' => trans('message.validation_meta_tags.topic_num_gt'),
            
            'keys.camp_num.required' => trans('message.validation_meta_tags.camp_num_required'),
            'keys.camp_num.numeric' => trans('message.validation_meta_tags.camp_num_numeric'),
            'keys.camp_num.gt' => trans('message.validation_meta_tags.camp_num_gt'),
            
            'keys.forum_num.required' => trans('message.validation_meta_tags.forum_num_required'),
            'keys.forum_num.numeric' => trans('message.validation_meta_tags.forum_num_numeric'),
            'keys.forum_num.gt' => trans('message.validation_meta_tags.forum_num_gt'),


            'keys.video_id.required' => trans('message.validation_meta_tags.video_id_required'),
            'keys.video_id.numeric' => trans('message.validation_meta_tags.video_id_numeric'),
            'keys.video_id.gt' => trans('message.validation_meta_tags.video_id_gt'),
            
        ];
    }

    public function notifyIfTopicNotExistValidationMessages(): array
    {
        return ([
            'is_type.required' => trans('message.notify_if_url_not_exist.is_type_required'),
            'topic_num.required_if' => trans('message.notify_if_url_not_exist.topic_num_required'),
            'topic_num.numeric' => trans('message.notify_if_url_not_exist.topic_num_numeric'),
            'topic_num.gt' => trans('message.notify_if_url_not_exist.topic_num_gt'),
            'camp_num.required_if' => trans('message.notify_if_url_not_exist.camp_num_required'),
            'camp_num.numeric' => trans('message.notify_if_url_not_exist.camp_num_numeric'),
            'camp_num.gt' => trans('message.notify_if_url_not_exist.camp_num_gt'),
            'nick_id.required_if' => trans('message.notify_if_url_not_exist.nick_id_required'),
            'nick_id.numeric' => trans('message.notify_if_url_not_exist.nick_id_numeric'),
            'nick_id.gt' => trans('message.notify_if_url_not_exist.nick_id_gt'),
            'thread_id.required_if' => trans('message.notify_if_url_not_exist.thread_id_required'),
            'thread_id.numeric' => trans('message.notify_if_url_not_exist.thread_id_numeric'),
            'thread_id.gt' => trans('message.notify_if_url_not_exist.thread_id_gt'),
            'url.required' => trans('message.notify_if_url_not_exist.url_required'),
        ]);
    }

    public function getLoginAsUserValidationMessages(): array
    {
        return [
            'id.required' => trans('message.validation_login_as_user_change.id_required'),
        ];
    }

    public function getEmbeddedCodeTrackingMessages(): array
    {
        return [
            'url.required' => trans('message.embedded_code_tracking_message.url_required'),
            'url.unique' => trans('message.embedded_code_tracking_message.url_unique'),
            'url.url' => trans('message.embedded_code_tracking_message.url_invalid'),
            'ip_address.ip' => trans('message.embedded_code_tracking_message.ip_invalid'),
            'user_agent.string' => trans('message.embedded_code_tracking_message.user_agent_string'),
        ];
    }

    public function getChangeSupportersValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_change_supporters.topic_num_required'),
            'topic_num.numeric' => trans('message.validation_change_supporters.topic_num_numeric'),
            'topic_num.gt' => trans('message.validation_change_supporters.topic_num_gt'),
            
            'camp_num.required' => trans('message.validation_change_supporters.camp_num_required'),
            'camp_num.numeric' => trans('message.validation_change_supporters.camp_num_numeric'),
            'camp_num.gt' => trans('message.validation_change_supporters.camp_num_gt'),    
            
            'change_id.required' => trans('message.validation_change_supporters.change_id_required'),
            'change_id.numeric' => trans('message.validation_change_supporters.change_id_numeric'),
            'change_id.gt' => trans('message.validation_change_supporters.change_id_gt'),     
            
            'type.required' => trans('message.validation_change_supporters.type_required'),
            'type.required' => trans('message.validation_change_supporters.type_in'),
        ];
    }

    public function getUserSupportsMessages(): array
    {
        return ([
            'namespace.integer' => trans('message.general.namespace_should_numeric'),
        ]);
    }

    public function getThreadByIdValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_change_supporters.topic_num_required'),
            'topic_num.numeric' => trans('message.validation_change_supporters.topic_num_numeric'),
            'topic_num.gt' => trans('message.validation_change_supporters.topic_num_gt'),
            
            'camp_num.required' => trans('message.validation_change_supporters.camp_num_required'),
            'camp_num.numeric' => trans('message.validation_change_supporters.camp_num_numeric'),
            'camp_num.gt' => trans('message.validation_change_supporters.camp_num_gt'),    
            
            'thread_id.exists' => trans('message.thread.thread_not_exist'),
        ];
    }

    public function getSignPetitionMessages(): array
    {
        return ([
            'topic_num.required' => trans('message.sign_petition_validation.topic_num_required'),
            'topic_num.numeric' => trans('message.sign_petition_validation.topic_num_numeric'),
            'topic_num.gt' => trans('message.sign_petition_validation.topic_num_gt'),
            'topic_num.max' => [
                'numeric' => trans('message.sign_petition_validation.topic_num_max_numeric')
            ],
            
            'camp_num.required' => trans('message.sign_petition_validation.camp_num_required'),
            'camp_num.numeric' => trans('message.sign_petition_validation.camp_num_numeric'),
            'camp_num.gt' => trans('message.sign_petition_validation.camp_num_gt'),    
            'camp_num.max' => [
                'numeric' => trans('message.sign_petition_validation.camp_num_max_numeric')
            ],

            'nick_name_id.required' => trans('message.sign_petition_validation.nick_name_id_required'),
            'nick_name_id.numeric' => trans('message.sign_petition_validation.nick_name_id_numeric'),
            'nick_name_id.gt' => trans('message.sign_petition_validation.nick_name_id_gt'),
            'nick_name_id.max' => [
                'numeric' => trans('message.sign_petition_validation.nick_name_id_max_numeric')
            ],
        ]);
    }
    
    public function getEmailUpdateValidationMessages(): array
    {
        return ([
            'email.required' => trans('message.validation_registration.email_required'),
            'email.string' => trans('message.validation_registration.email_string'),
            'email.email' => trans('message.validation_registration.email_email'),
            'email.max' => trans('message.validation_registration.email_max'),
            'email.unique' => trans('message.validation_registration.email_unique')
        ]);
    }
    public function getVerfiyAndUpdateEmaiMessages(): array
    {
        return ([
            'email.required' => trans('message.validation_registration.email_required'),
            'email.string' => trans('message.validation_registration.email_string'),
            'email.email' => trans('message.validation_registration.email_email'),
            'email.max' => trans('message.validation_registration.email_max'),
            'email.unique' => trans('message.validation_registration.email_unique'),
            'otp.required' => trans('message.otp.required'),
            'otp.digits' =>  trans('message.otp.valid_digits')
        ]);
    }

    public function getAddEmailMessages(): array
    {

        return ([
            'email.required' => trans('message.validation_registration.email_required'),
            'email.string' => trans('message.validation_registration.email_string'),
            'email.email' => trans('message.validation_registration.email_email'),
            'email.max' => trans('message.validation_registration.email_max'),
            'email.unique' => trans('message.validation_registration.email_unique')
        ]);
    }
}
