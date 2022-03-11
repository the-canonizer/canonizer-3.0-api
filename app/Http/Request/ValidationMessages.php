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
            'password.regex'=>'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..).',
            'first_name.regex' => 'The first name must be in alphabets and space only.',
            'first_name.required' => 'The first name field is required.',
            'first_name.max' => 'The first name can not be more than 100.',
            'middle_name.regex' => 'The middle name must be in alphabets and space only.',
            'middle_name.max' => 'The middle name can not be more than 100.',
            'last_name.regex' => 'The last name must be in alphabets and space only.',
            'last_name.required' => 'The last name field is required.',
            'last_name.max' => 'The last name can not be more than 100.',
            'password_confirmation.required' => 'The confirm password field is required.',
            'password_confirmation.same' => 'The password confirmation does not match.',
            'email.unique' => 'Email is already used.'
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
            'new_password.regex' => 'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..)',
            'current_password.required' => 'The current password field is required.'
        ]);
    }

    public function getUpdateProfileValidationMessages(): array
    {
        return([
            'first_name.regex' => 'The first name must be in alphabets and space only.',
            'last_name.regex' => 'The last name must be in alphabets and space only.',
            'middle_name.regex' => 'The middle name must be in alphabets and space only.',
            'city.regex' => 'The city name must be in alphabets and space only.',
            'state.regex' => 'The state name must be in alphabets and space only.',
            'country.regex' => 'The country name must be in alphabets and space only.',
            'postal_code.regex' => 'The postal code name must be in alphabets and space only.',
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
            'username.required' => 'The User Name field is required.',
            'new_password.regex' => 'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..)',
            'current_password.required' => 'The current password field is required.'
        ]);
    }

    public function getUserReSendOtpValidationMessages(): array
    {
        return ([]);
    }

    public function getStamenetValidationMessages(): array
    {
        return [
            'as_of.in'=>"Please enter a valid value (default,review,bydate) or leave it empty",
            'as_of_date.required_if'=>"Please enter as_of_date in case of bydate",
            'topic_num.required'=>"Please enter topic_num",
            'camp_num.required'=>"Please enter camp_num"
        ];
    }
    public function getNewsFeedValidationMessages(): array
    {
        return [
            'topic_num.required'=>"Please enter topic_num",
            'camp_num.required'=>"Please enter camp_num"
        ];
    }
    public function getAdsValidationMessages(): array
    {
        return [
            'page_name.required'=>"Please enter page_name",
            'page_name.string'=>"page_name should be a string"
        ];
    }
    public function getImageValidationMessages(): array
    {
        return [
            'page_name.required'=>"Please enter page_name",
            'page_name.string'=>"page_name should be a string"
        ];
    }

}
