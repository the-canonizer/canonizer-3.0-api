<?php

return [
    'error' => [
        'exception'      => 'Something went wrong',
        'update_profile' => 'Failed to update profile, please try again.',
        'verify_otp'     => 'Invalid One Time Verification Code.',
        'email_invalid'  => 'Invalid Email Id!',
        'account_not_verified'  => 'Your account is not verified!',
        'otp_failed' => 'Failed to Send OTP.',
        'otp_not_match' => 'OTP does not match',
        'email_not_registered' => 'Email is not registered with us!',
        'password_not_match' => 'Password does not match!',
        'user_not_exist' => 'User Does Not Exist!',
    ],
    'success' => [
        'success'          => 'Success',
        'password_change'  => 'Password changed successfully.',
        'update_profile'   => 'Profile updated successfully.',
        'verify_otp'       => 'Phone number has been verified successfully.',
        'forgot_password'  => 'Otp sent successfully on your Email Id.',
        'nick_name_update' => 'Nick name visibility status updated successfully.',
        'nick_name_add'    => 'Nick name added successfully.',
        'reg_success'      => 'Otp sent successfully on your registered Email Id',
        'reg_failed'       => 'Your Registration failed Please try again!',
        'phone_number_otp' => 'Otp has been sent on your phone number.'
    ],
    'validation_registration' => [
        'password_regex'=>'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..).',
        'first_name_regex' => 'The first name must be in alphabets and space only.',
        'first_name_required' => 'The first name field is required.',
        'first_name_max' => 'The first name can not be more than 100.',
        'middle_name_regex' => 'The middle name must be in alphabets and space only.',
        'middle_name_max' => 'The middle name can not be more than 100.',
        'last_name_regex' => 'The last name must be in alphabets and space only.',
        'last_name_required' => 'The last name field is required.',
        'last_name_max' => 'The last name can not be more than 100.',
        'password_confirmation_required' => 'The confirm password field is required.',
        'password_confirmation_same' => 'The password confirmation does not match.',
        'email_unique' => 'Email is already used.'
    ],
    'validation_change_password' => [
        'new_password_regex' => 'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..)',
        'current_password_required' => 'The current password field is required.',
        'new_password_required' => 'The new password field is required.',
        'confirm_password_required' => 'The confirm password field is required.',
        'confirm_password_match' => 'The new password and confirm password do not match.',
        'new_password_different' => 'Current and new password should not be same'
    ],
    'validation_update_profile' => [
        'first_name_regex' => 'The first name must be in alphabets and space only.',
        'last_name_regex' => 'The last name must be in alphabets and space only.',
        'middle_name_regex' => 'The middle name must be in alphabets and space only.',
        'city_regex' => 'The city name must be in alphabets and space only.',
        'state_regex' => 'The state name must be in alphabets and space only.',
        'country_regex' => 'The country name must be in alphabets and space only.',
        'postal_code_regex' => 'The postal code name must be in alphabets and space only.',
    ],
    'validation_forgot_password' => [
        'username_required' => 'The User Name field is required.',
        'new_password_regex' => 'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..)',
        'current_password_required' => 'The current password field is required.'
    ]
];
