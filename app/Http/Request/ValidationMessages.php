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
            'password.regex'=> trans('message.validation_registration.password_regex'),
            'first_name.regex' => trans('message.validation_registration.first_name_regex'),
            'first_name.required' => trans('message.validation_registration.first_name_required'),
            'first_name.max' => trans('message.validation_registration.first_name_max'),
            'middle_name.regex' => trans('message.validation_registration.middle_name_regex'),
            'middle_name.max' => trans('message.validation_registration.middle_name_max'),
            'last_name.regex' => trans('message.validation_registration.last_name_regex'),
            'last_name.required' => trans('message.validation_registration.last_name_required'),
            'last_name.max' => trans('message.validation_registration.last_name_max'),
            'password_confirmation.required' => trans('message.validation_registration.password_confirmation_required'),
            'password_confirmation.same' => trans('message.validation_registration.password_confirmation_same'),
            'email.unique' => trans('message.validation_registration.email_unique'),
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
        return([
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
        return([
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

    public function getForgotPasswordSendOtpValidationMessages(): array
    {
        return([
            'email.required' => trans('message.validation_forgot_password.email_required'),
        ]);
    }

    public function getForgotPasswordVerifyOtpValidationMessages(): array
    {
        return([
            'otp.required' => trans('message.validation_forgot_password.otp_required'),
            'username.required' => trans('message.validation_forgot_password.email_required'),
        ]);
    }

    public function getForgotPasswordUpdateValidationMessages(): array
    {
        return([
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
        return([
            'display_text.required' => trans('message.validation_update_newsfeed.display_text_required'),
            "display_text.regex" => trans('message.validation_update_newsfeed.display_text_regex'),
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
        return([
            'camp_name.regex' => trans('message.validation_camp_store.camp_name_regex'),
            'nick_name.required' => trans('message.validation_camp_store.nick_name_required'),
            'camp_name.required' => trans('message.validation_camp_store.camp_name_required'),
            'camp_name.max' => trans('message.validation_camp_store.camp_name_max'),
            'camp_name.unique' => trans('message.validation_camp_store.camp_name_unique'),
            'camp_about_url.max' => trans('message.validation_camp_store.camp_about_url_max'),
            'camp_about_url.regex' => trans('message.validation_camp_store.camp_about_url_regex'),
            'parent_camp_num.required' => trans('message.validation_camp_store.parent_camp_num_required'),
            'objection.required' => trans('message.validation_camp_store.objection_required'),
            'objection_reason.max' => trans('message.validation_camp_store.objection_reason_max'),
            'asof.in' => trans('message.validation_camp_store.asof_in')
        ]);
    }

    public function getTopicStoreValidationMessages(): array
    {
        return([
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
        return([
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
            'camp_num.required' => trans('message.validation_get_camp_record.camp_num_required')
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
        return  ([
            'file.required' => trans('message.uploads.image_required'),
            'file.*.mimes' => trans('message.uploads.image_mimes'),
            'file.*.max' => trans('message.uploads.image_size'),
            'name.*.required' => trans('message.uploads.image_name_required'),

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
            'title.max' => trans('message.thread.title_max')
        ];
    }
    
    public function getStatementHistoryValidationMessages(): array
    {
        return([
            'topic_num.required' => trans('message.validation_get_statementHistory.topic_num_required'),
            'camp_num.required' => trans('message.validation_get_statementHistory.camp_num_required'),
            'type.in' => trans('message.validation_get_statementHistory.type_in'),
            'as_of.in' => trans('message.validation_get_statementHistory.as_of_in'),
            'as_of_date.required_if' => trans('message.validation_get_statementHistory.as_of_date_required_if'),
        ]);
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

}
