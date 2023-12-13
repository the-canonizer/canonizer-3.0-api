<?php

return [
    'error' => [
        'exception'      => 'Something went wrong',
        'update_profile' => 'Failed to update profile, please try again.',
        'file_does_not_exists' => 'File does not exists.',
        'verify_otp'     => 'Invalid One Time Verification Code.',
        'email_invalid'  => 'Invalid Email Id!',
        'account_not_verified'  => 'Error! Your account is not verified yet. You must have received the verification code in your registered email. If not then you can request for new code by clicking on the button below.',
        'in_active_message'  => 'Your account has been suspended temporarily. Please contact support@canonizer.com for further assistance.',
        'otp_failed' => 'Failed to Send OTP.',
        'otp_not_match' => 'OTP does not match',
        'email_not_registered' => 'Email is not registered with us!',
        'password_not_match' => 'Password does not match!',
        'user_not_exist' => 'User Does Not Exist!',
        'reg_failed'       => 'Your Registration failed Please try again!',
        'topic_failed' => 'Fail to create topic, please try later.',
        'camp_failed' => 'Fail to create camp, please try later.',
        'camp_alreday_exist' => 'Camp name has already been taken.',
        'invalid_camp_name' => 'Invalid camp name.',
        'topic_name_alreday_exist' => 'Topic name has already been taken.',
        'invalid_data' => 'The given data was invalid.',
        'record_not_found' => 'No record found.',
        'topic_not_found' => 'No topic found.',
        'camp_not_found' => 'No camp found.',
        'topic_record_not_found' => 'No topic record found.',
        'camp_record_not_found' => 'No camp record found.',
        'camp_news_feed_not_found' => 'No camp news feed found.',
        'camp_live_statement_not_found' => 'No camp statement yet.',
        'camp_breadcrumb_not_found' => 'No camp breadcrumb found.',
        'algorithms_not_found' => 'No algorithms found.',
        'support_list_not_found' => 'No support list found.',
        'no_activity_logged' => 'No activity logged!',
        'forum_not_found' => 'No forum found.',
        'otp_lenth_match' => 'OTP should be min/max 6 characters long!',
        'objection_history_changed' => 'Cannot object to the :history. It is already live.',
        'disagree_history_changed' => 'Cannot disagree because this :history is already live.',
        'disagree_objected_history_changed' => 'Cannot disagree because this :history is already objected.',
        'camp_archive_change_is_already_submitted' => 'Cannot resubmit the camp unarchive change.'
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
        'statement_update'  => 'Statement updated successfully.',
        'statement_commit'  => 'Your change to statement has been submitted to your supporters.',
        'statement_agree'  => ' Your agreement to statement is submitted successfully.',
        'topic_agree'  => ' Your agreement to topic is submitted successfully.',
        'topic_not_agree'  => ' Your agreement to topic is removed successfully.',
        'camp_agree'  => ' Your agreement to camp is submitted successfully.',
        'camp_commit'  => 'Your change to statement has been submitted to your supporters.',
        'topic_commit'  => 'Your change to topic has been submitted to your supporters.',
        'topic_object'  => 'Objection submitted successfully.',
        'topic_update'  => 'Topic updated successfully.',
    ],
    'general' => [
        'nickname_association_absence' => "Nickname not associated.",
        'permission_denied' => "You don't have permission to access this resource.",
        'support_added' => "Support added",
        'support_removed' => "Support removed",
        'support_delegated' => "Support delegated",
        'default_support_added_reason' => "Default Support added on agreement",
        "namespace_should_numeric" => "Namespace should be integer"
    ],
    'validation_registration' => [
        'first_name_regex' => 'The first name must be in alphabets and space only.',
        'first_name_required' => 'The first name field is required.',
        'first_name_max' => 'The first name can not be more than 100.',
        'first_name_string' => 'The first name should be string.',

        'middle_name_regex' => 'The middle name must be in alphabets and space only.',
        'middle_name_required' => 'The middle name field is required.',
        'middle_name_max' => 'The middle name can not be more than 100.',
        'middle_name_string' => 'The middle name should be string.',

        'last_name_regex' => 'The last name must be in alphabets and space only.',
        'last_name_required' => 'The last name field is required.',
        'last_name_max' => 'The last name can not be more than 100.',
        'last_name_string' => 'The last name should be string.',

        'email_required' => 'The email field is required.',
        'email_string' => 'The email should be string.',
        'email_email' => 'The email should be a valid email.',
        'email_max' => 'The email can not be more than 225.',
        'email_unique' => 'The email is already used..',
        
        'password_required' => 'Password is required.',
        'password_regex' => 'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..).',
        
        'password_confirmation_required' => 'The confirm password field is required.',
        'password_confirmation_same' => 'The password confirmation does not match.',
        
        'phone_number_unique' => 'Phone number is already used.',
        
        'country_code_required' => 'The country code is required.',
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
        
        'profile_picture_required' => 'Profile Picture is required.',
        'profile_picture_file' => 'The :attribute must be a file.',
        'profile_picture_mimes' => 'The :attribute must be a file of type: png,jpg,jpeg.',
        'profile_picture_size' => 'The profile picture must not be greater than 1024KB.',
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
        'camp_creation_not_allowed' => "Camp creation is disabled under this topic or camp!",
        'camp_only_one_level_allowed' => "Camp creation only one level allowed under this topic or camp!",
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
        'camp_num_required' => "Camp number is required",
        'topic_num_numeric' => "Topic Num should be a numeric.",
        'camp_num_numeric' => "Camp Num should be a numeric.",
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
        'name_not_received' => 'Name is not returned from social account. You have to enter the First Name and Last Name.',
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
        'folder_name_updated' => 'Folder name updated successfully.',
        'image_name_unique' => 'File name already exists, please try different file name.'

    ],
    'validation_store_newsfeed' => [
        'topic_num_required' => 'Topic number is required.',
        'camp_num_required' => 'Camp number is required.',
        'link_regix' => 'Link is invalid. (Example: https://www.example.com?post=1234).',
        'available_for_child_required' => 'Availability for child is required.',
        'link_required' => 'Link is required.',
        'display_text_required' => 'Display text is required.',
        'display_text_max' => 'Display text may not be greater than 256 characters.',
        'submitter_nick_id_required' => 'Submitter nickname id is required.',
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
        'thread_id_required' => 'Thread id is required.',
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
        'thread_not_exist' => 'Requested thread does not exist.',
        'thread_not_related' => "Thread doesn't exist for requested camp"
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
        'type_in' => "Please enter a valid type value (objected, in_review, old, live, all) or leave it empty",
        'as_of_in' => "Please enter a valid value (default,review,bydate) or leave it empty",
        'as_of_date_required_if' => "Asof date is required in case of asof bydate",
        'per_page_required' => "Records per page is required.",
        'page_required' => "Page number is required.",
    ],
    'validation_edit_newsfeed' => [
        'newsfeed_id_required' => "Newsfeed id is required.",
        'not_found' => 'No record found against provided id.',
    ],
    'validation_edit' => [
        'record_id_required' => "Record id is required.",
        'event_type_required' => 'Event type is required.',
    ],
    'support'  => [
        'add_direct_support' => 'Support added successfully.',
        'add_delegate_support' => 'You have delegated your support successfully.',
        'add_delegation_support' => 'Support delegated successfully.',
        'complete_support_removed' => 'Support removed successfully.',
        'order_update' => 'Support order updated successfully',
        'delegate_support_removed' => 'Your delegation has been removed successfully.',
        'delegate_invalid_request' => 'Invalid request, please try again later.',
        'support_exist' => 'This camp is already supported',
        'support_not_exist' => "This camp doesn't have your support",
        'update_support' => 'Support updated successfully.',
        'remove_direct_support' => 'Support removed successfully.',
        'not_authorized_for_objection' => 'You are not authorized to object to this statement because you do not support this camp.',
        'not_authorized_for_objection_topic' => 'You are not authorized to object to this topic because you do not support this camp.',
        'not_authorized_for_objection_camp' => 'You are not authorized to object to this camp because you do not support this camp.'



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
        'event_type_required' => "Event type is required.",
        'event_type_in' => "Possible values are create, update, edit, objection.",
        'statement_id_required_if' => "Statement id is required.",
        'objection_reason_required_if' => "Objection reason is required.",
    ],
    'validation_get_camp_bread_crumb' => [
        'as_of_in' => "Please enter a valid value (default,review,bydate) or leave it empty",
        'as_of_date_required_if' => "Asof date is required in case of asof bydate",
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required"
    ],
    'validation_commit_change' => [
        'type_in' => "Please enter a valid value (topic,camp,statement)",
        'id_required' => "Id is required.",
    ],
    'validation_discard_change' => [
        'id_required' => "Id is required.",
        'type_required' => "type is required (topic, camp, statement)",
        'type_in' => "Please enter a valid value (topic, camp, statement)",
    ],
    'statement_comparison' => [
        'ids_required' => "Statement Ids is required.",
        'topic_num_required' => "Topic Num is required.",
        'camp_num_required' => "Camp Num is required.",
    ],
    'support_warning' => [
        'not_live' => "You can not submit your support to this camp as its not live yet.",
        'not_found' => "You can not submit your support to this camp, as system is unable to find this camp."
    ],
    'validation_get_topic_activity_log' => [
        'topic_num_required' => "Topic number is required.",
        'topic_num_integer' => "Topic number can only be an integer.",
        'camp_num_required' => "Camp number is required",
        'camp_num_integer' => "Camp number can only be an integer."
    ],
    'validation_agree_to_change' => [
        'record_id_required' => "Record id is required.",
        'user_agreed_required' => 'User agreed is required.',
        'user_agreed_boolean' => 'User agreed should be 0 & 1.',
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required.",
        'change_for_in' => "Please enter a valid value (topic,camp,statement)",
        'nick_name_id_required' => "Nick name id is required",
        'change_for_required' => "Change for is required. Allowed values are (topic,camp,statement)",
    ],
    'validation_get_topic_history' => [
        'topic_num_required' => "Topic number is required.",
        'type_in' => "Please enter a valid type value (live ,objected, in_review, old, all) or leave it empty",
        'per_page_required' => "Records per page is required.",
        'page_required' => "Page number is required.",
    ],
    'validation_get_camp_history' => [
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number is required.",
        'type_in' => "Please enter a valid type value (live ,objected, in_review, old, all) or leave it empty",
        'per_page_required' => "Records per page is required.",
        'page_required' => "Page number is required.",
    ],
    'validation_manage_camp' => [
        'topic_num_required' => "Topic number is required.",
        'camp_num_required' => "Camp number number is required.",
        'camp_id_required' => "Camp id is required.",
        'nick_name_required' => "Nick name is required.",
        'submitter_required' => "submitter id is required.",
        'event_type_required' => "Event type is required.",
        'event_type_in' => "Possible values are update, edit, objection.",
        'camp_name_required' => 'Camp name is required.',
        'objection_reason_required_if' => "Objection reason is required.",
        'camp_about_url_regex' => 'Camp about url is invalid.',
    ],
    'validation_check_camp_status' => [
        'topic_num_required' => "The topic num is required.",
        'topic_num_integer' => "The topic num must be an integer.",
        'topic_num_max_numeric' => "The topic num must not be greater than 9223372036854775807.",
        'topic_num_exists' => "The topic not found.",

        'camp_num_required' => "The camp num is required.",
        'camp_num_integer' => "The camp num must be an integer.",
        'camp_num_max_numeric' => "The camp num must not be greater than 9223372036854775807.",
    ],
    'notification_title' => [
        "createTopic" => "Topic Create",
        "createCamp" => "New Camp Created!",
        "createThread" => "New Thread Created!",
        "createPost" => "New Post Created!",
        "updatePost" => "Post Updated!",
        "manageStatement" => "Change proposed on Camp - :camp_name",
        "addSupport" => "Support Added to Camp - :camp_name",
        "removeSupport" => "Support Removed from Camp - :camp_name",
        "addDelegateSupport" => "Delegate Support Added to topic - :topic_name",
        "addDelegateSupportUser" => "Delegate Your Support to topic - :topic_name",
        "promotedDelegate" => "You have been Promoted to Your delegate's place. in camp - :camp_name under topic - :topic_name",
        "promotedDirect" => "You have been promoted as direct supporter in camp - :camp_name under topic - :topic_name.",
        "commitTopicChange" => "Proposed a change to topic - :topic_name.",
        "commitCampChange" => "Proposed a change to camp - :camp_name.",
        "commitStatementChange" => "Proposed a change to statement of camp - :camp_name under topic - :topic_name",
        "objectCamp" => ":nick_name has objected to your proposed change to camp",
        "objectTopic" => ":nick_name has objected to your proposed change to topic",
        "objectStatement" => ":nick_name has objected to your proposed change to statement",
    ],
    'notification_message' => [
        "fcm_token_required" => "Fcm Token is required.",
        "createTopic" => "Hello :nick_name, You proposed a change for :notification_type : :topic_name",
        "createCamp" => ":nick_name has created a new Camp - :camp_name",
        "createThread" => ":nick_name has created a new Thread :thread_name under Camp - :camp_name",
        "createPost" => ":nick_name has made the new Post under Thread - :thread_name",
        "updatePost" => ":nick_name has updated the Post under Thread - :thread_name",
        "manageStatement" => ":nick_name has proposed a change to the statement for Camp - :camp_name",
        "addSupport" => ":nick_name has just added support to the Camp - :camp_name",
        "removeSupport" => ":nick_name  has just removed support from the Camp - :camp_name",
        "addDelegateSupport" => ":nick_name has just added delegate support to :delegate_nick_name in this topic - :topic_name",
        "addDelegateMessage" => ":nick_name has just added delegate support to you in this topic - :topic_name",
        "addDelegateSupportUser" => "You have delegated your support to :delegate_nick_name in this topic - :topic_name",
        "promotedDelegate" => "You delegated your support to :nick_name  who delegate their support to :delegated_nick_name, now has removed their delegated support from camp - :camp_name under topic - :topic_name. So your support has been delegated to :delegated_nick_name.",
        "promotedDirect" => "You delegated your support to :nick_name who was directly supporting camp - :camp_name under topic - :topic_name, now has removed their entire support from this topic. So you have been promoted to a direct supporter",
        "commitCampChange" => ":nick_name has just proposed a change to - :camp_name camp.",
        "commitStatementChange" => ":nick_name has just proposed a change to statement of - :camp_name camp.",
        "commitTopicChange" => ":nick_name has just proposed a change to - :topic_name topic.",
        "objectCamp" => ":nick_name has objected to your proposed change submitted for Camp (:topic_name/:camp_name)",
        "objectTopic" => ":nick_name has objected to your proposed change submitted for topic (:topic_name)",
        "objectStatement" => ":nick_name has objected to your proposed change submitted for Camp (:topic_name/:camp_name) statement",
    ],
    'validation_manage_topic' => [
        'topic_num_required' => "Topic number is required.",
        'topic_id_required' => "Camp number number is required.",
        'nick_name_required' => "Nick name is required.",
        'submitter_required' => "submitter id is required.",
        'event_type_required' => "Event type is required.",
        'event_type_in' => "Possible values are update, edit, objection.",
        'topic_name_required' => 'Camp name is required.',
        'objection_reason_required_if' => "Objection reason is required.",
        
    ],
    'validation_parse_statement' => [
        'value_required' => "string to be parsed is required."
    ],
    'activity_log_message' => [
        "camp_create" => ":nick_name created a camp",
        "camp_update" => ":nick_name updated a camp",
        "camp_object" => ":nick_name objected a change to camp",
        
        "support_removed" => ":nick_name removed support",
        "support_added" => ":nick_name added support",
        
        "news_create" => ":nick_name added a news",
        "news_update" => ":nick_name updated a news",
        "news_delete" => ":nick_name deleted a news",
        
        "statement_create" => ":nick_name created a statement",
        "statement_update" => ":nick_name updated a statement",
        "statement_object" => ":nick_name objected a change to statement",
        
        "thread_create" => ":nick_name created a thread",
        "thread_update" => ":nick_name updated a thread",
        
        "post_create" => ":nick_name created a post",
        "post_update" => ":nick_name updated a post",
        
        "topic_create" => ":nick_name created a topic",
        "topic_update" => ":nick_name updated a topic",
        "topic_object" => ":nick_name objected a change to topic",
        "commit_change" => ":nick_name proposed a change to the :type",
        "delegate_support" => ":nick_name delegated their support to :delegate_to",
        "remove_delegated_support" => ":nick_name removed delegated support from :delegate_to.",
    ],
    "validation_meta_tags" => [
        'page_name_required' => "page_name is required.",
        'page_name_string' => "page_name should be a string.",
        'page_name_alpha' => "page_name should be must be entirely alphabetic characters.",

        'topic_num_required' => "topic_num is required.",
        'topic_num_numeric' => "topic_num should be a numeric.",
        'topic_num_gt' => "topic_num should be greater than :value.",

        'camp_num_required' => "camp_num is required.",
        'camp_num_numeric' => "camp_num should be a numeric.",
        'camp_num_gt' => "camp_num should be greater than :value.",

        'forum_num_required' => "forum_num is required.",
        'forum_num_numeric' => "forum_num should be a numeric.",
        'forum_num_gt' => "forum_num should be greater than :value.",
    ],
    "validation_change_supporters" => [
        'topic_num_required' => "topic_num is required.",
        'topic_num_numeric' => "topic_num should be a numeric.",
        'topic_num_gt' => "topic_num should be greater than :value.",

        'camp_num_required' => "camp_num is required.",
        'camp_num_numeric' => "camp_num should be a numeric.",
        'camp_num_gt' => "camp_num should be greater than :value.",

        'change_id_required' => "change_id is required.",
        'change_id_numeric' => "change_id should be a numeric.",
        'change_id_gt' => "change_id should be greater than :value.",
        
        'type_required' => ":attribute is required.",
        'type_in' => 'The selected :attribute is invalid.',
        
    ],
    "notify_if_url_not_exist" => [
        'is_type_required' => "is_type is required.",
        'topic_num_required' => "topic_num is required.",
        'topic_num_numeric' => "topic_num should be a numeric.",
        'topic_num_gt' => "topic_num should be greater than :value.",
        'camp_num_required' => "camp_num is required.",
        'camp_num_numeric' => "camp_num should be a numeric.",
        'camp_num_gt' => "camp_num should be greater than :value.",
        'nick_id_required' => "nick_id is required.",
        'nick_id_numeric' => "nick_id should be a numeric.",
        'nick_id_gt' => "nick_id should be greater than :value.",
        'thread_id_required' => "thread_id is required.",
        'thread_id_numeric' => "thread_id should be a numeric.",
        'thread_id_gt' => "thread_id should be greater than :value.",
        'url_required' => "url is required.",
    ],
    "camp" => [
        "camp_archived" => 'archived',
        "camp_archived_direct_summary" => "This camp has been archived directly",
        "camp_archived_indirectly_summary" => "This camp has been archived implicitly.",
        "camp_unarchived" => "unarchived",
        "camp_unarchived_summary" => "Support added back, because camp is restored.",
    ],
    'validation_login_as_user_change' => [
        'id_required' => "Id is required.",
    ],
    'embedded_code_tracking_message' => [
        'url_required' => "The url field is required.",
        'url_invalid' => "The url must be a valid URL format",
        'url_unique' => "The url has already been taken.",
        'ip_invalid' => "The IP address must be a valid IP format.",
        'user_agent_string' => "The :attribute must be a valid user agent string.",
        
    ],
];
