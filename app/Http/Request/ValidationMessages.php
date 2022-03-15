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



}
