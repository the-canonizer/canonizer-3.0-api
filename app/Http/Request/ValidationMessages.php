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
        return ([]);
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
        return ([]);
    }

    public function getForgotPasswordVerifyOtpValidationMessages(): array
    {
        return ([]);
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

    public function getStamenetValidationMessages(): array
    {
        return [
            'as_of.in' => "Please enter a valid value (default,review,bydate) or leave it empty",
            'as_of_date.required_if' => "Please enter as_of_date in case of bydate",
            'topic_num.required' => "Please enter topic_num",
            'camp_num.required' => "Please enter camp_num"
        ];
    }
    public function getNewsFeedValidationMessages(): array
    {
        return [
            'topic_num.required' => "Please enter topic_num",
            'camp_num.required' => "Please enter camp_num"
        ];
    }
    public function getAdsValidationMessages(): array
    {
        return [
            'page_name.required' => "Please enter page_name",
            'page_name.string' => "page_name should be a string"
        ];
    }
    public function getImageValidationMessages(): array
    {
        return [
            'page_name.required' => "Please enter page_name",
            'page_name.string' => "page_name should be a string"
        ];
    }
    public function getNewsFeedUpdateValidationMessages($request): array
    {
        $messages["display_text.*.required"] = 'Display text is required.';
        $messages["display_text.*.regex"] = 'Display text can only contain space, full stop (.) and alphanumeric characters.';
        $messages["display_text.*.max"] = 'Display text may not be greater than 256 characters.';
        $messages['display_text.required'] = "display_text is required";
        $messages['display_text.array'] = "display_text should be an array";
        $messages["link.*.regex"] = 'Link is invalid. (Example: https://www.example.com?post=1234)';
        $messages["link.*.required"] = 'Link is required.';
        $messages['link.size'] = 'Size of all arrays must be same';
        $messages['link.array'] = "link should be an array";
        $messages['link.required'] = "link is required";
        $messages["available_for_child.*.boolean"] = "Please enter boolean value for children's availability";
        $messages['available_for_child.required'] = "available_for_child is required";
        $messages['available_for_child.array'] = "available_for_child should be an array";
        $messages['available_for_child.size'] = 'Size of all arrays must be same';
        $messages['topic_num.required'] = "Please enter topic_num";
        $messages['camp_num.required'] = "Please enter camp_num";
        return $messages;
    }

    public function getNewsFeedEditValidationMessages(): array
    {
        return [
            'topic_num.required' => "Please enter topic_num",
            'camp_num.required' => "Please enter camp_num"
        ];
    }
}
