<?php

namespace Tests;

use App\Models\User;

class GetNewsFeedApiTest extends TestCase
{
    public function testWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $apiPayload = [];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-newsfeed', $apiPayload, $header);
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
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-newsfeed', $apiPayload, $header);
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
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-newsfeed', $apiPayload, $header);
        //  dd($response);
        $response->assertStatus(404);
    }

    public function testIfNewsFeedFound()
    {
        $apiPayload = [
            'topic_num' => 88,
            'camp_num' => 1
        ];
        print sprintf("\nTest if newsfeed found");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-newsfeed', $apiPayload, $header);
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
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-breadcrumb', $data, $header);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }
}
