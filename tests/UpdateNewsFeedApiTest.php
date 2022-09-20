<?php

use App\Models\User;

class UpdateNewsFeedApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testUpdateNewsFeedApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/update-camp-newsfeed', []);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testUpdateNewsFeedApiWithEmptyValues()
    {
        $emptyData = [
            "newsfeed_id"=>"",
            "display_text" => "",
            "link" => "",
            "available_for_child" => "",
            "submitter_nick_id"=>""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/update-camp-newsfeed', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testUpdateNewsFeedApiWithInvalidData()
    {
        $invalidData = [
            "newsfeed_id"=>"abc",
            "display_text" => "xyz",
            "link" => "facebook.com",
            "available_for_child" => 1,
            "submitter_nick_id"=>"abc"
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/update-camp-newsfeed', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api response code with valid data
     */
    public function testUpdateNewsFeedApiStatus()
    {
        $data = [
            "newsfeed_id"=>1,
            "display_text" => "abc",
            "link" => "facebook.com",
            "available_for_child" =>  1,
            "submitter_nick_id"=>1010
        ];
        print sprintf("\n Update NewsFeed ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post(
            '/api/v3/update-camp-newsfeed',
            $data
        );
        $this->assertEquals(200, $this->response->status());
    }
}
