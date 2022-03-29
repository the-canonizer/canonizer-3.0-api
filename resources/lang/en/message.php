<?php

return [
    'error' => [
        'exception'      => 'Something went wrong',
        'update_profile' => 'Failed to update profile, please try again.',
        'verify_otp'     => 'Invalid One Time Verification Code.',
        'email_invalid'  => 'Invalid Email Id!',
        'account_not_verified'  => 'Error! Your account is not verified yet. You must have received the verification code in your registered email or mobile. If not then you can request for new code by clicking on the button below.',
        'otp_failed' => 'Failed to Send OTP.',
        'otp_not_match' => 'OTP does not match',
        'email_not_registered' => 'Email is not registered with us!',
        'password_not_match' => 'Password does not match!',
        'user_not_exist' => 'User Does Not Exist!',
        'reg_failed'       => 'Your Registration failed Please try again!',
        'topic_failed' => 'Fail to create topic, please try later.',
        'camp_failed' => 'Fail to create camp, please try later.',
        'camp_alreday_exist' => 'Camp name has already been taken.',
    ],
    'success' => [
        'success'          => 'Success',
        'password_change'  => 'Password changed successfully.',
        'update_profile'   => 'Profile updated successfully.',
        'verify_otp'       => 'Phone number has been verified successfully.',
        'forgot_password'  => 'OTP sent successfully on your Email Id.',
        'nick_name_update' => 'Nick name visibility status updated successfully.',
        'nick_name_add'    => 'Nick name added successfully.',
        'reg_success'      => 'OTP sent successfully on your registered Email Id',
        'phone_number_otp' => 'OTP has been sent on your phone number.',
        'topic_created' => 'Topic created successfully.',
        'camp_created' => 'Camp created successfully.',
        'password_reset' => 'Your password has been reset successfully.',
        'user_remove'  => 'User remove successfully.',
        
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
        'first_name_required' => 'First name is required.',
        'first_name_regex' => 'The first name must be in alphabets and space only.',
        'last_name_required' => 'Last name is required.',
        'last_name_regex' => 'The last name must be in alphabets and space only.',
        'middle_name_regex' => 'The middle name must be in alphabets and space only.',
        'city_regex' => 'The city name must be in alphabets and space only.',
        'state_regex' => 'The state name must be in alphabets and space only.',
        'country_regex' => 'The country name must be in alphabets and space only.',
        'postal_code_regex' => 'The postal code name must be in alphabets and space only.',
    ],
    'validation_forgot_password' => [
        'username_required' => 'The User Name field is required.',
        'otp_required' => 'The OTP field is required.',
        'email_required' => 'Please enter a valid email address',
        'new_password_regex' => 'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..)',
        'current_password_required' => 'The current password field is required.'
    ],
    'validation_camp_store' => [
            'camp_name_regex' => 'Camp name can only contain space and alphanumeric characters.',
            'nick_name_required' => 'The nick name field is required.',
            'camp_name_required' => 'Camp name is required.',
            'camp_name_max' => 'Camp name can not be more than 30 characters.',
            'camp_name_unique' => 'The camp name has already been taken.',
            'camp_about_url_max' => "Camp's about url can not be more than 1024 characters.",
            'camp_about_url_regex' => "The camp about url format is invalid. (Example: https://www.example.com?post=1234)",
            'parent_camp_num_required' => 'The parent camp name is required.',
            'objection_required' => 'Objection reason is required.',
            'objection_reason_max' => 'Objection reason can not be more than 100.',
            'asof_in' => "Please enter a valid asof value (default,review,bydate) or leave it empty",
    ],
    'validation_topic_store' => [
        'topic_name_required' => 'Topic name is required.',
        'topic_name_max' => 'Topic name can not be more than 30 characters.',
        'topic_name_regex' => 'Topic name can only contain space and alphanumeric characters.',
        'topic_name_unique' => 'The topic name has already been taken.',
        'namespace_required' => 'Namespace is required.',
        'create_namespace_required_if' => 'The Other Namespace Name field is required when namespace is other.',
        'create_namespace_max' => 'The Other Namespace Name can not be more than 100 characters.',
        'nick_name_required' => 'Nick name is required.',
        'objection_reason_required' => 'Objection reason is required.',
        'objection_reason_max' => 'Objection reason can not be more than 100.',
        'asof_in' => "Please enter a valid asof value (default, review, bydate) or leave it empty",
    ],
    'validation_get_statement' => [ 
        'as_of_in' => "Please enter a valid value (default,review,bydate) or leave it empty",
        'as_of_date_required_if' => "Asof date is required in case of asof bydate",
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required"
    ],
    'validation_get_newsfeed' => [ 
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required"
    ],
    'validation_get_ads' => [ 
        'page_name_required' => "Page name is required.",
        'page_name_string' => "Page name should be a string"
    ],
    'validation_get_image' => [ 
        'page_name_required' => "Page name is required.",
        'page_name_string' => "Page name should be a string"
    ],
    'validation_edit_newsfeed' => [ 
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required"
    ],
    'validation_update_newsfeed' => [ 
        'display_text_*_required' => 'Display text is required.',
        'display_text_*_regex' => 'Display text can only contain space, full stop (.) and alphanumeric characters.',
        'display_text_*_max' => 'Display text may not be greater than 256 characters.',
        'display_text_required' => 'Display text is required',
        'display_text_array' => 'Display text should be an array',
        'link_*_regex' => 'Link is invalid. (Example: https://www.example.com?post=1234)',
        'link_*_required' => 'Link is required.',
        'link_size' => 'Size of all arrays must be same',
        'link_array' => 'Link should be an array',
        'link_required' => 'Link is required',
        'available_for_child_*_boolean' => "Please enter boolean value for children's availability",
        'available_for_child_required' => 'Availability for child is required',
        'available_for_child_array' => 'Availability for child should be an array',
        'available_for_child_size' => 'Size of all arrays must be same',
        'topic_num_required' => 'Topic number is required.',
        'camp_num_required' => 'Camp number is required'
    ],

    'phone_number' => [
        'required' => 'Phone number is required',
        'mobile_carrier_required' => 'Mobile carrier is required',
        'valid_digits' => 'Invalid Phone number. It must be 10 digits phone number.'
    ],

    'otp' => [
        'required' => 'Otp is required',
        'valid_digits' => 'Enter valid 6 digit otp.'
    ],

    'verify_otp' => [
        'otp_required' => 'OTP is required.',
        'username_required' => 'Email is required.',
        'client_id_required' => 'Client Id is required.',
        'client_secret_required' => 'Client Secrect is required.',
    ],
    'validation_get_camp_record' => [ 
        'as_of_in' => "Please enter a valid value (default,review,bydate) or leave it empty",
        'as_of_date_required_if' => "Asof date is required in case of asof bydate",
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required"
    ],
    'validation_get_topic_record' => [ 
        'as_of_in' => "Please enter a valid value (default,review,bydate) or leave it empty",
        'as_of_date_required_if' => "Asof date is required in case of asof bydate",
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required"
    ],
    'get_all_parent_camp' => [
        'topic_num_required' => 'Topic Num is required.'
    ],
    'social' => [
        'user_id_required' => 'User id is required.',
        'client_id_required' => 'Client id is required.',
        'client_secret_required' => 'Client Secret is required.',
        'provider_required' => 'Provider is required.',
        'code_required' => 'Code is required.',
        'unlink_social_user' => 'Social Link deleted successfully',
        'successfully_linked' => 'Your social account is linked successfully',
        'already_linked' => 'Email is already linked with another account',
    ]
];
