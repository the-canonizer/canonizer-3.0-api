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
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'city' => 'nullable',
            'state' => 'nullable',
            'country' => 'nullable',
            'postal_code' => 'nullable',
            'phone_number' => 'nullable|digits:10',
        ]);
    }

    public function getForgotPasswordSendOtpValidationRules(): array
    {
        return ([
            'email' => 'required|string|email|max:225|regex:/^\S*$/u',
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
            'topic_num' => 'required|exists:statement,topic_num',
            'camp_num' => 'required',
            'as_of' => 'in:default,review,bydate',
            'as_of_date' => 'required_if:as_of,bydate'
        ]);
    }

    public function getEditCaseValidationRules(): array
    {
        return ([
            'record_id' => 'required',
            'event_type' => 'required'
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

    public function getNewsFeedDeleteValidationRules(): array
    {
        return [
            'newsfeed_id' => 'required|exists:news_feed,id',
        ];
    }

    public function getNewsFeedEditValidationRules(): array
    {
        return [
            'newsfeed_id' => 'required|exists:news_feed,id',
        ];
    }

    public function getNewsFeedUpdateValidationRules(): array
    {
        return [
            'display_text' => 'required|max:256',
            'link' => ['required','regex:/(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|^(www)\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/^(?:www\.|(?!www))[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/[a-zA-Z0-9][^\s]{2,}|^[a-zA-Z0-9]+\.[^\s]{2,})/'],
            'available_for_child' => 'required|boolean',
            'newsfeed_id' => 'required|exists:news_feed,id',
            'submitter_nick_id' => 'required'
        ];
    }

    public function getCampStoreValidationRules(): array
    {
        $regex = '/(http(s?):\/\/)([a-z0-9\-]+\.)+[a-z]{2,4}(\.[a-z]{2,4})*(\/[^ ]+)*/i';

        return ([
            'nick_name' => 'required',
            'camp_name' => 'required|max:30',
            'camp_about_url' => 'nullable|max:1024|regex:'.$regex,
            'parent_camp_num' => 'nullable',
            'asof' => 'in:default,review,bydate'
        ]);
    }

    public function getTopicStoreValidationRules(): array
    {
        return ([
            'topic_name' => 'required|max:30',
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

    public function getUploadFileValidationRules(): array
    {
        return([
            'file' => 'required',
            'file.*' => 'max:5120',
            //'file.*' => 'mimes:jpeg,bmp,png,jpg,gif',
            'name.*' => 'required|unique:uploads,file_name,NULL,id,deleted_at,NULL'
        ]);
    }
    
    public function getNewsFeedStoreValidationRules(): array
    {
        return [
            'topic_num' => 'required',
            'camp_num' => 'required',
            'available_for_child' => 'required|boolean',
            "link" => ['required','regex:/(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|^(www)\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/^(?:www\.|(?!www))[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/[a-zA-Z0-9][^\s]{2,}|^[a-zA-Z0-9]+\.[^\s]{2,})/'],
            "display_text" => 'required|max:256',
            "submitter_nick_id" => 'required'
        ];
    }

    public function getThreadStoreValidationRules(): array
    {
        return ([
            'title'    => 'required|max:100',
            'nick_name' => 'required',
            'camp_num' => 'required',
            'topic_num' => 'required',
            'topic_name' => 'required',
        ]);
    }

    public function getThreadListValidationRules(): array
    {
        return ([
            'camp_num' => 'required',
            'topic_num' => 'required',
            'type' => 'required',
        ]);
    }

    public function getThreadUpdateValidationRules(): array
    {
        return ([
            'title'    => 'required|max:100',
            'camp_num' => 'required',
            'topic_num' => 'required',
        ]);
    }

    public function getPostStoreValidationRules(): array
    {
        return ([
            'body' => 'required',
            'nick_name' => 'required',
            'camp_num' => 'required',
            'topic_num' => 'required',
            'topic_name' => 'required',
            'thread_id' => 'required',
        ]);
    }

    public function getPostUpdateValidationRules(): array
    {
        return ([
            'body' => 'required',
            'camp_num' => 'required',
            'topic_num' => 'required',
            'topic_name' => 'required',
            'thread_id' => 'required',
        ]);
    }
    
    public function getStatementHistoryValidationRules(): array
    {
        return [
            'topic_num' => 'required',
            'camp_num' => 'required',
            'type' => 'in:objected,in_review,old,live,all',
            'as_of' => 'in:default,review,bydate',
            'as_of_date' => 'required_if:as_of,bydate',
            'per_page' => 'required',
            'page' => 'required',
        ];
    }

    public function getAddDirectSupportRule(): array
    {
        return [
            'topic_num' => 'required|integer',
            'nick_name_id' => 'required',
            //'camps' => 'required|array|min:1',
            //'camps.*.camp_num' => 'required|integer',
            //'camps.*.support_order' => 'required|integer'
        ];
    }

    public function getAddDelegateSupportRule(): array
    {
        return [
            'topic_num' => 'required|integer',
            'nick_name_id' => 'required|integer',
            'delegated_nick_name_id' => 'required|integer'
        ];
    }

    public function getAllCampSubscriptionValidationRules(): array
    {
        return [
            'topic_num' => 'required',
            'camp_num' => 'required',
            'checked' => 'required|boolean',
            'subscription_id' => 'required_if:checked,false'
        ];
    }

    public function getActivityLogValidationRules(): array
    {
        return [
            'per_page' => 'required',
            'page' => 'required',
            'log_type' => 'required|in:topic/camps,threads'
        ];
    }

    public function getStatementStoreValidationRules(): array
    {
        return [
            'topic_num' => 'required',
            'camp_num' => 'required',
            'statement' => 'required',
            'nick_name' => 'required',
            'submitter' => 'required',
            'event_type' => 'required|in:create,update,edit,objection', 
            'statement_id' => 'required_if:event_type,objection|required_if:event_type,edit',
            'objection_reason' => 'required_if:event_type,objection'
        ];
    }
    
    public function getPostVerifyEmailValidationRules(): array
    {
        return ([
            'client_id' => 'required',
            'client_secret' => 'required',
            'provider' => 'required',
            'code' => 'required',
            'otp' => 'required',
            'email' =>  'required|string|email|max:225|regex:/^\S*$/u',
        ]);
    }

    public function getReSendOtpVerifyEmailValidationRules(): array
    {
        return ([
            'client_id' => 'required',
            'client_secret' => 'required',
            'provider' => 'required',
            'code' => 'required',
            'email' =>  'required|string|email|max:225|regex:/^\S*$/u',
        ]);
    }

    public function getCampBreadCrumbValidationRules(): array
    {
        return ([
            'topic_num' => 'required',
            'camp_num' => 'required',
            'as_of' => 'in:default,review,bydate',
            'as_of_date' => 'required_if:as_of,bydate'
        ]);
    }

    public function getCommitChangeValidationRules(): array
    {
        return ([
            'id' => 'required',
            'type' => 'in:statement,camp,topic',
        ]);
    }

    public function getDiscardChangeValidationRules(): array
    {
        return ([
            'id' => 'required',
            'type' => 'required|in:statement,camp,topic',
        ]);
    }
    
    public function getStatementComparisonValidationRules(): array
    {
        return ([
            'ids' => 'required',
            'topic_num' => 'required',
            'camp_num' => 'required',
        ]);
    }

    public function getCampActivityLogValidationRules(): array
    {
        return [
            'topic_num' => 'required|integer',
            'camp_num' => 'required|integer'
        ];
    }

    public function getAgreeToChangeValidationRules(): array
    {
        return ([
            'record_id' => 'required',
            'user_agreed' => 'required|boolean',
            'topic_num' => 'required',
            'camp_num' => 'required',
            'change_for' => 'required|in:topic,camp,statement',
            'nick_name_id' => 'required',
        ]);
    }

    public function getAgreeToChangeForLiveJobValidationRules(): array
    {
        return ([
            'record_id' => 'required',
            'topic_num' => 'required',
            'camp_num' => 'required',
            'change_for' => 'required|in:topic,camp,statement',
        ]);
    }

    public function getTopicHistoryValidationRules(): array
    {
        return ([
            'topic_num' => 'required',
            'per_page' => 'required',
            'page' => 'required',
            'type' => 'in:objected,live,in_review,old,all',
        ]);
    }

    public function getCampHistoryValidationRules(): array
    {
        return ([
            'topic_num' => 'required',
            'camp_num' => 'required',
            'type' => 'in:objected,live,in_review,old,all',
            'per_page' => 'required',
            'page' => 'required',
        ]);
    }
    
    public function getManageCampValidationRules(): array
    {
        return [
            'topic_num' => 'required',
            'camp_num' => 'required',
            'camp_id' => 'required',
            'nick_name' => 'required',
            'submitter' => 'required',
            'camp_name' => 'required',
            'camp_about_url' => ['regex:/(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|^(www)\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/^(?:www\.|(?!www))[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/[a-zA-Z0-9][^\s]{2,}|^[a-zA-Z0-9]+\.[^\s]{2,})/','nullable'],
            'event_type' => 'required|in:update,edit,objection', 
            'objection_reason' => 'required_if:event_type,objection'
        ];
    }

    public function getManageTopicValidationRules(): array
    {
        return [
            'topic_num' => 'required',
            'topic_id' => 'required',
            'submitter' => 'required',
            'topic_name' => 'required',
            'nick_name' => 'required',
            'namespace_id' => 'required',
            'event_type' => 'required|in:update,edit,objection', 
            'objection_reason' => 'required_if:event_type,objection'
        ];
    }

    public function getUpdateFcmTokenValidationRules(): array
    {
        return ([
            'fcm_token' => 'required',
        ]);
    }

    public function getParseStatementValidationRules(): array
    {
        return ([
            'value' => 'present|string'
        ]);
    }

    public function getMetaTagsValidationRules(): array
    {
        return [
            'page_name' => 'required|string|alpha',
        ];
    }

    public function getMetaTagsByTopicCampValidationRules(): array
    {
        return [
            'keys.topic_num' => 'required|numeric|gt:0',
            'keys.camp_num' => 'required|numeric|gt:0',
        ];
    }

    public function notifyIfTopicNotExistValidationRules(): array
    {
        return [
            'topic_num' => 'required_if:is_type,topic|required_if:is_type,statement|numeric|gt:0',
            'camp_num' => 'required_if:is_type,topic|required_if:is_type,statement|numeric|gt:0',
            'nick_id' => 'required_if:is_type,nickname|numeric|gt:0',
            'thread_id' => 'required_if:is_type,thread|numeric|gt:0',
            'url' => 'required',
        ];
    }

    public function getLoginAsUserValidationRules(): array
    {
        return ([
            'id' => 'required',
        ]);
    }

    public function getEmbeddedCodeTrackingRules(): array
    {
        return ([
            'url' => 'required|unique:embedded_code_tracking|url',
            'ip_address' => 'sometimes|nullable|ip',
            'user_agent' => 'sometimes|nullable|string',
        ]);
    }

    public function getChangeSupportersValidationRules(): array
    {
        return [
            'topic_num' => 'required|numeric|gt:0',
            'camp_num' => 'required|numeric|gt:0',
            'change_id' => 'required|numeric|gt:0',
            'type' => 'required|in:topic,camp,statement',
        ];
    }

    public function getUserSupportsValidationRules(): array
    {
        return ([
            'namespace' => 'integer'
        ]);
    }

    public function getThreadByIdValidationRules(): array
    {
        return ([
            'camp_num' => 'required|numeric|gt:0',
            'topic_num' => 'required|numeric|gt:0',
            'thread_id' => 'exists:thread,id'
        ]);
    }
}
