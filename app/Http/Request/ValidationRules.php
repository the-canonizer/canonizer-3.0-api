<?php

namespace App\Http\Request;

class ValidationRules
{
    public function getTokenValidationRules(): array
    {
        return ([
            'client_id' => 'required',
            'client_secret' => 'required',
        ]);
    }

    public function getLoginValidationRules(): array
    {
        return ([
            'username' => 'required',
            'password' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
        ]);
    }


    public function getRegistrationValidationRules(): array
    {
        return ([
            'first_name' => 'required|regex:/^[a-zA-Z ]*$/|string|max:100',
            'last_name' => 'required|regex:/^[a-zA-Z ]*$/|string|max:100',
            'middle_name' => 'nullable|regex:/^[a-zA-Z ]*$/|max:100',
            'email' => 'required|string|email|max:225|unique:person',
            'password' => ['required','regex:/^(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{8,}$/'],
            'password_confirmation' => 'required|same:password',
            'phone_number' => 'unique:person',
            'country_code' => 'required',
        ]);
    }

    public function getVerifyOtpValidationRules(): array
    {
        return ([
            'otp' => 'required',
            'username' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
        ]);
    }

    public function getSocialLoginValidationRules(): array
    {
        return ([
            'provider' => 'required'
        ]);
    }

    public function getSocialCallbackValidationRules(): array
    {
        return ([
            'client_id' => 'required',
            'client_secret' => 'required',
            'provider' => 'required',
            'code' => 'required'
        ]);
    }

    public function getChangePasswordValidationRules(): array
    {
        return ([
            'current_password' => 'required',
            'new_password' => ['required', 'regex:/^(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{8,}$/', 'different:current_password'],
            'confirm_password' => 'required|same:new_password'
        ]);
    }

    public function getUpdateProfileValidatonRules(): array
    {
        return ([
            'first_name' => 'required|regex:/^[a-zA-Z ]*$/|string|max:100',
            'last_name' => 'required|regex:/^[a-zA-Z ]*$/|string|max:100',
            'middle_name' => 'nullable|regex:/^[a-zA-Z ]*$/|string|max:100',
            'city' => 'nullable|regex:/^[a-zA-Z ]*$/|string|max:100',
            'state' => 'nullable|regex:/^[a-zA-Z ]*$/|string|max:100',
            'country' => 'nullable|regex:/^[a-zA-Z ]*$/|string|max:100',
            'postal_code' => 'nullable|regex:/^[a-zA-Z0-9 ]*$/|string|max:100',
            'phone_number' => 'nullable|digits:10',
        ]);
    }

    public function getForgotPasswordSendOtpValidationRules(): array
    {
        return ([
            'email' => 'required|string|email|max:225',
        ]);
    }

    public function getForgotPasswordVerifyOtpValidationRules(): array
    {
        return ([
            'otp' => 'required',
            'username' => 'required',
        ]);
    }

    public function getForgotPasswordUpdateValidationRules(): array
    {
        return([
            'username' => 'required',
            'new_password' => ['required', 'regex:/^(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{8,}$/', 'different:current_password'],
            'confirm_password' => 'required|same:new_password'
        ]);
    }
    public function getVerifyPhoneValidatonRules(): array
    {
        return ([
            'phone_number' => 'required|digits:10',
            'mobile_carrier' => 'required'
        ]);
    }

    public function getVerifyOtpValidatonRules(): array
    {
        return ([
            'otp' => 'required|digits:6',
        ]);
    }

    public function getUserReSendOtpValidationRules(): array
    {
        return ([
            'email' => 'required|string|email|max:225',
        ]);
    }
    public function getStatementValidateionRules(): array
    {
        return ([
            'topic_num' => 'required',
            'camp_num' => 'required',
            'as_of' => 'in:default,review,bydate',
            'as_of_date' => 'required_if:as_of,bydate'
        ]);
    }
    public function getNewsFeedValidateionRules(): array
    {
        return [
            'topic_num' => 'required',
            'camp_num' => 'required',
        ];
    }

    public function getAdsValidateionRules(): array
    {
        return [
            'page_name' => 'required|string'
        ];
    }
    public function getImageValidateionRules(): array
    {
        return [
            'page_name' => 'required|string'
        ];
    }

    
}
