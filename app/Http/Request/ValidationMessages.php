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
            'password_confirmation.same' => 'The password confirmation does not match.'
        ]);
    }

    public function getVerifyOtpValidationMessages(): array
    {
        return ([]);
    }

    public function getSocialValidationMessages(): array
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
}
