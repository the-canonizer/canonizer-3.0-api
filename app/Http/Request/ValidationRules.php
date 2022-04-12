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

    public function getStatementValidationRules(): array
    {
        return ([
            'topic_num' => 'required',
            'camp_num' => 'required',
            'as_of' => 'in:default,review,bydate',
            'as_of_date' => 'required_if:as_of,bydate'
        ]);
    }

    public function getNewsFeedValidationRules(): array
    {
        return [
            'topic_num' => 'required',
            'camp_num' => 'required',
        ];
    }

    public function getAdsValidationRules(): array
    {
        return [
            'page_name' => 'required|string'
        ];
    }

    public function getImageValidationRules(): array
    {
        return [
            'page_name' => 'required|string'
        ];
    }

    public function getNewsFeedEditValidationRules(): array
    {
        return [
            'topic_num' => 'required',
            'camp_num' => 'required',
        ];
    }

    public function getNewsFeedUpdateValidationRules($sizeLimit): array
    {
        return [
            'display_text' => 'required|array',
            'display_text.*' => 'required|max:256|regex:/^[a-zA-Z0-9.\s]+$/',
            "link" => 'required|array|size:'. $sizeLimit,
            "link.*" => 'required|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
            'available_for_child' => 'required|array|size:'. $sizeLimit,
            'available_for_child.*' => 'required|boolean',
            'topic_num' => 'required',
            'camp_num' => 'required'
        ];
    }

    public function getCampStoreValidationRules(): array
    {
        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';

        return ([
            'nick_name' => 'required',
            'camp_name' => 'required|unique:camp|max:30|regex:/^[a-zA-Z0-9\s]+$/',
            'camp_about_url' => 'nullable|max:1024|regex:'.$regex,
            'parent_camp_num' => 'nullable',
            'asof' => 'in:default,review,bydate'
        ]);
    }

    public function getTopicStoreValidationRules(): array
    {
        return ([
            'topic_name' => 'required|max:30|unique:topic|regex:/^[a-zA-Z0-9\s]+$/',
            'namespace' => 'required',
            'create_namespace' => 'required_if:namespace,other|max:100',
            'nick_name' => 'required',
            'asof' => 'in:default,review,bydate'
        ]);
    }

    public function getCampRecordValidationRules(): array
    {
        return ([
            'topic_num' => 'required',
            'camp_num' => 'required',
            'as_of' => 'in:default,review,bydate',
            'as_of_date' => 'required_if:as_of,bydate'
        ]);
    }
    public function getTopicRecordValidationRules(): array
    {
        return ([
            'topic_num' => 'required',
            'camp_num' => 'required',
            'as_of' => 'in:default,review,bydate',
            'as_of_date' => 'required_if:as_of,bydate'
        ]);
    }

    public function getAllParentCampValidationRules(): array
    {
        return ([
            'topic_num' => 'required'
        ]);
    }

    public function getDeactivateUserValidationRules(): array
    {
        return ([
            'user_id' => 'required'
        ]);
    }

    public function getNewsFeedStoreValidationRules(): array
    {
        return [
            'topic_num' => 'required',
            'camp_num' => 'required',
            'available_for_child' => 'required|boolean',
            "link" => 'required|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
            "display_text" => 'required'
        ];
    }

    public function getThreadStoreValidationRules(): array
    {
        return ([
            'title'    => 'required|max:100|regex:/^[a-zA-Z0-9\s]+$/',
            'nick_name' => 'required',
            'camp_num' => 'required',
            'topic_num' => 'required',
            'topic_name' => 'required',
        ]);
    }
}
