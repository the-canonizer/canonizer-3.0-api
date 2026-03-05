<?php

namespace Tests;

use App\Models\User;
use App\Models\NewsFeed;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateNewsFeedApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Check Api with empty form data
     * validation
     */
    public function testUpdateNewsFeedApiWithEmptyFormData() 
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->create([
            'type' => 'admin',
            'status' => 1
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/update-camp-newsfeed', []);
        $response->assertStatus(400);
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
        $user = User::factory()->create([
            'type' => 'admin',
            'status' => 1
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/update-camp-newsfeed', $emptyData);
        $response->assertStatus(400);
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
        $user = User::factory()->create([
            'type' => 'admin',
            'status' => 1
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/update-camp-newsfeed', $invalidData);
        $response->assertStatus(400);
    }

    /**
     * Check Api response code with valid data
     */
    public function testUpdateNewsFeedApiStatus() 
    {
        $user = User::factory()->create(['type' => 'admin', 'status' => 1]);
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $newsFeed = NewsFeed::factory()->create(['submitter_nick_id' => $nickname->id]);
        $data = [
            "newsfeed_id"=> $newsFeed->id,
            "display_text" => "abc",
            "link" => "facebook.com",
            "available_for_child" =>  1,
            "submitter_nick_id" => $nickname->id
        ];
        print sprintf("\n Update NewsFeed ", 200, PHP_EOL);
        
        $response = $this->actingAs($user)->postJson(
            '/api/v3/update-camp-newsfeed',
            $data
        );
        $response->assertStatus(200);
    }
}
