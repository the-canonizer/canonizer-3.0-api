<?php

use App\Models\User;

class StoreNewsFeedApiTest extends TestCase
{
    
    /**
     * Check Api with empty form data
     * validation
     */
    public function testStoreNewsFeedApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $this->actingAs($user)->post('/api/v3/store-camp-newsfeed', []);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testStoreNewsFeedApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "camp_num" => "",
            "display_text" => "",
            "link" => "",
            "available_for_child" => "",
            "submitter_nick_id" => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $this->actingAs($user)->post('/api/v3/store-camp-newsfeed', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testStoreNewsFeedApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => 2,
            "camp_num" => 1,
            "display_text" => "xyz",
            "link" => "facebook.com",
            "available_for_child" => "boolean",
            "submitter_nick_id" => "0"
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $this->actingAs($user)->post('/api/v3/store-camp-newsfeed', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testStoreNewsFeedWithoutUserAuth()
    {
        print sprintf("Test with empty form data");
        $this->post('/api/v3/store-camp-newsfeed', []);
        $this->assertEquals(401,  $this->response->status());
    }
}
