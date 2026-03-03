<?php

namespace Tests;

use App\Models\User;

class GetCampActivityLogApiTest extends TestCase
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
        $response = $this->actingAs($user)->post('/api/v3/get-camp-activity-log', $apiPayload, $header);
        $response->assertStatus(400);
    }

    public function testWithEmptyValues()
    {
        print sprintf("\nTest with empty values");
        $apiPayload = [
            'topic_num' => '',
            'camp_num' => ''
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-activity-log', $apiPayload, $header);
        $response->assertStatus(400);
    }

    public function testWithInvaidTopicNum()
    {
        print sprintf("\nTest with invalid topic_num");
        $apiPayload = [
            'topic_num' => 12312312,
            'camp_num' => 1
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-activity-log', $apiPayload, $header);
        $response->assertStatus(404);
    }

    public function testIfActivityIsNotLogged()
    {
        print sprintf("\nTest if activity is not logged");
        $apiPayload = [
            'topic_num' => 88,
            'camp_num' => 2
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-activity-log', $apiPayload, $header);
        $response->assertStatus(404);
    }

    public function testWithValidValues()
    {
        print sprintf("\nTest with valid values");
        $apiPayload = [
            'topic_num' => 88,
            'camp_num' => 1
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-activity-log', $apiPayload, $header);
        $response->assertStatus(200);
    }

    public function testApiStructureValidValues()
    {
        print sprintf("\nTest api structure with valid values");
        $apiPayload = [
            'topic_num' => 88,
            'camp_num' => 1
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/get-camp-activity-log', $apiPayload, $header);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                'items' => []
            ]
        ]);
    }
}
