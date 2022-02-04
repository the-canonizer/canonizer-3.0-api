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
            'phone_number' => 'required|unique:person',
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
        return([
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
        ]);
    }
}
