<?php

namespace Tests;

use App\Models\User;
use App\Models\NewsFeed;

class UpdateNewsFeedApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testUpdateNewsFeedApiWithEmptyFormData() 
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/update-camp-newsfeed', []);
        $_res->assertStatus(400);
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
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/update-camp-newsfeed', $emptyData);
        $_res->assertStatus(400);
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
            "available_for_child" => "abc",
            "submitter_nick_id"=>"abc"
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/update-camp-newsfeed', $invalidData);
        $_res->assertStatus(400);
    }

    /**
     * Check Api response code with valid data
     */
    public function testUpdateNewsFeedApiStatus() 
    {
        $newsFeed = NewsFeed::factory()->create();
        $data = [
            "newsfeed_id"=> $newsFeed->id,
            "display_text" => "abc",
            "link" => "facebook.com",
            "available_for_child" =>  1,
            "submitter_nick_id"=>1
        ];
        print sprintf("\n Update NewsFeed ", 200, PHP_EOL);
        $user = User::find(1);
        $this->actingAs($user)->post(
            '/api/v3/update-camp-newsfeed',
            $data
        );
        $_res->assertStatus(200);
    }
}
