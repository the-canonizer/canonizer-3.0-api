<?php

namespace App\Http\Request;

class ValidationMessages
{
    public function getTokenValidationMessages(): array
    {
        return ([]);
    }

    public function getLoginValidationMessages(): array
    {
        return ([]);
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
        return ([]);
    }

    public function getSocialCallbackValidationMessages(): array
    {
        return ([]);
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
            'first_name.regex' => trans('message.validation_update_profile.first_name_regex'),
            'last_name.regex' => trans('message.validation_update_profile.last_name_regex'),
            'middle_name.regex' => trans('message.validation_update_profile.middle_name_regex'),
            'city.regex' => trans('message.validation_update_profile.city_regex'),
            'state.regex' => trans('message.validation_update_profile.state_regex'),
            'country.regex' => trans('message.validation_update_profile.country_regex'),
            'postal_code.regex' => trans('message.validation_update_profile.postal_code_regex'),
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
        return ([]);
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
            'display_text.*.required' => trans('message.validation_update_newsfeed.display_text_*_required'),
            "display_text.*.regex" => trans('message.validation_update_newsfeed.display_text_*_regex'),
            "display_text.*.max" => trans('message.validation_update_newsfeed.display_text_*_max'),
            'display_text.required' => trans('message.validation_update_newsfeed.display_text_required'),
            'display_text.array' => trans('message.validation_update_newsfeed.display_text_array'),
            "link.*.regex" => trans('message.validation_update_newsfeed.link_*_regex'),
            "link.*.required" => trans('message.validation_update_newsfeed.link_*_required'),
            'link.size' => trans('message.validation_update_newsfeed.link_size'),
            'link.array' => trans('message.validation_update_newsfeed.link_array'),
            'link.required' => trans('message.validation_update_newsfeed.link_required'),
            "available_for_child.*.boolean" => trans('message.validation_update_newsfeed.available_for_child_*_boolean'),
            'available_for_child.required' => trans('message.validation_update_newsfeed.available_for_child_required'),
            'available_for_child.array' => trans('message.validation_update_newsfeed.available_for_child_array'),
            'available_for_child.size' => trans('message.validation_update_newsfeed.available_for_child_size'),
            'topic_num.required' => trans('message.validation_update_newsfeed.topic_num_required'),
            'camp_num.required' => trans('message.validation_update_newsfeed.camp_num_required'),
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

    public function getNewsFeedEditValidationMessages(): array
    {
        return [
            'topic_num.required' => trans('message.validation_edit_newsfeed.topic_num_required'),
            'camp_num.required' => trans('message.validation_edit_newsfeed.camp_num_required')
        ];
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
}
