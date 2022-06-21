<?php

return [
    'error' => [
        'exception'      => 'Something went wrong',
        'update_profile' => 'Failed to update profile, please try again.',
        'verify_otp'     => 'Invalid One Time Verification Code.',
        'email_invalid'  => 'Invalid Email Id!',
        'account_not_verified'  => 'Error! Your account is not verified yet. You must have received the verification code in your registered email. If not then you can request for new code by clicking on the button below.',
        'otp_failed' => 'Failed to Send OTP.',
        'otp_not_match' => 'OTP does not match',
        'email_not_registered' => 'Email is not registered with us!',
        'password_not_match' => 'Password does not match!',
        'user_not_exist' => 'User Does Not Exist!',
        'reg_failed'       => 'Your Registration failed Please try again!',
        'topic_failed' => 'Fail to create topic, please try later.',
        'camp_failed' => 'Fail to create camp, please try later.',
        'camp_alreday_exist' => 'Camp name has already been taken.',
        'invalid_data' => 'The given data was invalid.',
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
        'user_remove'  => 'User removed successfully.',
        'news_feed_add'  => 'Newsfeed added successfully.',
        'otp_verified'  => 'OTP verified successfully.',
        'subscribed'  => 'Subscribed successfully.',
        'unsubscribed'  => 'Unsubscribed successfully.',
        'statement_create'  => 'Statement submitted successfully.',
        'statement_object'  => 'Objection submitted successfully.',
    ],
    'general' => [
        'nickname_association_absence' => "Nickname not associated.",
        'permission_denied' => "You don't have permission to access this resource."
    ],
    'validation_registration' => [
        'password_regex' => 'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..).',
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
        'current_password_required' => 'The current password field is required.',
        'email_max' => 'Email can not be more than 225 characters.'
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
    'validation_delete_newsfeed' => [
        'newsfeed_id_required' => "Newsfeed id is required.",
        'not_found' => 'No record found against provided id.',
    ],
    'validation_update_newsfeed' => [
        'display_text_max' => 'Display text may not be greater than 256 characters.',
        'display_text_required' => 'Display text is required',
        'link_regex' => 'Link is invalid. (Example: https://www.example.com?post=1234)',
        'link_required' => 'Link is required.',
        'available_for_child_boolean' => "Please enter boolean value for children's availability",
        'available_for_child_required' => 'Availability for child is required',
        'submitter_nick_id_required' => 'Submitter nick name id is required',  
        'newsfeed_id_required' => "Newsfeed id is required.",
        'not_found' => 'No record found against provided id.',
    ],

    'phone_number' => [
        'required' => 'Phone number is required',
        'mobile_carrier_required' => 'Mobile carrier is required',
        'valid_digits' => 'Invalid Phone number. It must be 10 digits phone number.'
    ],

    'otp' => [
        'required' => 'OTP is required.',
        'valid_digits' => 'Please enter 6-digit code.'
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
        'not_linked' => 'Your social account is not linked with any user',
        'email_not_received' => 'Your email address is not returned from social account. You have to enter the email address.',
    ],
    'uploads' => [
        'folder_created' => "Folder created successfully.",
        'success' => "File(s) uploaded successfully.",
        'image_required' => 'Please upload an image.',
        'image_mimes' => 'Only jpeg,bmp,png,jpg and gif files are allowed.',
        'image_size' => 'Sorry! Maximum allowed size for an file is 5MB.',
        'image_name_required' => 'Please provide name for every file.',
        'folder_has_files_can_not_delete' => 'This folder contains files, hence can not be deleted.',
        'folder_deleted' => 'Folder has been deleted successfully.',
        'not_found' => 'Folder not found!',
        'folder_not_found' => "Folder you are trying to delete does not exists or already deleted.",
        'file_not_found' => "File you are trying to delete does not exists or already deleted.",
        'file_in_use' => "You can not delete this file, as this is used in one or more statements.",
        'file_deleted' => "This file has been deleted successfully.",
        'folder_name_updated' => 'Folder name updated successfully.'

    ],
    'validation_store_newsfeed' => [
        'topic_num_required' => 'Topic number is required.',
        'camp_num_required' => 'Camp number is required.',
        'link_regix' => 'Link is invalid. (Example: https://www.example.com?post=1234).',
        'available_for_child_required' => 'Availability for child is required.',
        'link_required' => 'Link is required.',
        'display_text_required' => 'Display text is required.',
        'display_text_max' => 'Display text may not be greater than 256 characters.',
        'submitter_nick_id_required'=> 'Submitter nickname id is required.',
    ],
    'login' => [
        'username_required' => 'Email is required.',
        'password_required' => 'Password is required.',
        'client_id_required' => 'Client Id is required.',
        'client_secret_required' => 'Client Secrect is required.',
    ],
    'reSendOTP' => [
        'email_required' => 'Email is required.',
    ],
    'thread' => [
        'title_regex' => 'Title can only contain space and alphanumeric characters.',
        'title_required' => 'Title is required.',
        'title_max' => 'Title can not be more than 100 characters.',
        'title_unique' => 'Thread title must be unique!',
        'nick_name_required' => 'The nick name field is required.',
        'camp_num_required' => 'Camp num is required.',
        'topic_num_required' => 'Topic num is required.',
        'topic_name_required' => 'Topic name is required.',
        'create_success' => 'Thread Created Successfully!',
        'create_failed' => 'Fail to create thread, please try later.',
        'not_authorized' => 'You are not authorized to access this API.',
        'type_required' => 'Type field is required.',
        'id_not_exist' => 'Given thread id does not exist in the database.',
        'update_success' => 'Thread title updated successfully.',
    ],
    'post' => [
        'nick_name_required' => 'Nick name field is required.',
        'body_regex' => 'Reply field is required.',
        'create_success' => 'Post Created Successfully!',
        'create_failed' => 'Fail to create post, please try later.',
        'camp_num_required' => 'Camp num is required.',
        'topic_num_required' => 'Topic num is required.',
        'topic_name_required' => 'Topic name is required.',
        'thread_id_required' => 'Thread id is required.',
        'post_not_exist' => "Post id doesn't exist.",
        'update_success' => 'Post Updated Successfully',
        'delete_success' => 'Post deleted successfully.',
    ],
    'validation_get_statementHistory' => [
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required",
        'type_in' => "Please enter a valid type value (objected, in_review, old, all) or leave it empty",
        'as_of_in' => "Please enter a valid value (default,review,bydate) or leave it empty",
        'as_of_date_required_if' => "Asof date is required in case of asof bydate",
        'per_page_required' => "Records per page is required.",
        'page_required' => "Page number is required.",
    ],
    'validation_edit_newsfeed' => [
        'newsfeed_id_required' => "Newsfeed id is required.",
        'not_found' => 'No record found against provided id.',
    ],
    'support'  => [
        'add_direct_support' => 'Support added successfully.',
        'add_delegate_support' => 'You have delegated your support successfully.',
        'add_delegation_support' => 'Support delegated successfully.',
        'complete_support_removed' => 'Support removed successfully.',
        'order_update' => 'Support order updated successfully',
        'delegate_support_removed' => 'Your delegation has been removed successfully.',
        'delegate_invalid_request' => 'Invalid request, please try again later.'

    ],
    'support_validation' => [
        'nick_name_required' => 'Nick name is required',
        'topic_num_required' => 'Invalid topic or no topic submitted.',
        'camps_required' => "Atleast one camp should be submitted to add the support.",
        'camp_num_required' => "Camp number missing.",
        "camp_num_invalid" => "Invalid camp number submitted.",
        "support_order_required" => "Support order is missing.",
        'support_order_invalid' => "Invalid support order submitted, it can only be integer."
    ],
    'delegate_support_validation' => [
        'nick_name_required' => 'Nick name is required',
        'topic_num_required' => 'Invalid topic or no topic submitted.',
        'delegate_nick_name_required' => 'Invalid delegation, please provide valid delegated user.'
    ],
    'validation_subscription_camp' => [
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required",
        'checked_boolean' => "Please enter boolean value for Checked",
        'checked_required' => 'Checked is required',
        'subscription_id_required' => "Subscription id is required in case of unsubscribe",
        'subscription_id_not_found' => "No record found against provided subscription id.",
        'already_subscribed' => "Already subscribed.",
        'already_unsubscribed' => "Already unsubscribed.",
    ],
    'validation_get_activity_log' => [
        'per_page_required' => "Records per page is required.",
        'page_required' => "Page number is required.",
        'log_type_required' => "Log type is required.",
        'log_type_in' => "Please enter a valid Log Type value (topic/camps, threads)",
    ],
    'validation_store_statement' => [
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number number is required.",
        'statement_required' => "Statement is required.",
        'nick_name_required' => "Nick name is required.",
        'submitter_required' => "submitter id is required.",
        'parent_camp_num_required' => "Parent camp number is required.",
        'statement_id_required_if' => "Statement id is required.",
        'objection_reason_required_if' => "Objection reason is required.",
    ],
    'validation_get_camp_bread_crumb' => [
        'as_of_in' => "Please enter a valid value (default,review,bydate) or leave it empty",
        'as_of_date_required_if' => "Asof date is required in case of asof bydate",
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required"
    ]
];
