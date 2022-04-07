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
        $user = User::factory()->make();
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
            "available_for_child" => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
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
            "available_for_child" => "boolean"
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/store-camp-newsfeed', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api response code with valid data
     */
    public function testStoreNewsFeedApiStatus()
    {
        $data = [
            "topic_num" => 2,
            "camp_num" => 1,
            "display_text" => "xyz",
            "link" => "facebook.com",
            "available_for_child" => 1
        ];
        print sprintf("\n Store NewsFeed ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post(
            '/api/v3/store-camp-newsfeed',
            $data
        );
        $this->assertEquals(200, $this->response->status());
    }
}
