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
}
