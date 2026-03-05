<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetNewsFeedApiTest extends TestCase
{
    use DatabaseTransactions;
    public function testWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $apiPayload = [];
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-newsfeed', $apiPayload);
        //  dd($response);
        $response->assertStatus(400);
    }

    public function testWithEmptyValues()
    {
        $apiPayload = [
            'topic_num' => '',
            'camp_num' => ''
        ];
        print sprintf("\nTest with empty values");
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-newsfeed', $apiPayload);
        //  dd($response);
        $response->assertStatus(400);
    }

    public function testIfNoNewsFeedFound()
    {
        $apiPayload = [
            'topic_num' => 2,
            'camp_num' => 1
        ];
        print sprintf("\nTest if no newsfeed found");
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-newsfeed', $apiPayload);
        //  dd($response);
        $response->assertStatus(200);
    }

    public function testIfNewsFeedFound()
    {
        $apiPayload = [
            'topic_num' => 88,
            'camp_num' => 1
        ];
        print sprintf("\nTest if newsfeed found");
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-newsfeed', $apiPayload);
        //  dd($response);
        $response->assertStatus(200);
    }

    public function testToSeeApiStructure()
    {
        $data = [
            'topic_num' => 88,
            'camp_num' => 1,
            "as_of" => "default",
            "as_of_date" => 1696854130.086
        ];

        print sprintf("\n Test for correct api structure ");
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-newsfeed', $data);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }
}
