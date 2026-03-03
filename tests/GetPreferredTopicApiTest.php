<?php

namespace Tests;

use App\Models\User;
class GetPreferredTopicApiTest extends TestCase
{

    public function testGetPreferredTopicApi()
    {
        print sprintf("Call the get preferred Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->get('/api/v3/preferred-topic',$header);
        $response->assertStatus(200);
    }
    public function testGetPreferredTopicApiWithInvalidMetgod()
    {
        print sprintf("Call the get preferred Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/preferred-topic',$header);
        $response->assertStatus(405);
    }
    public function testGetPreferredTopicApiWithInvalidURL()
    {
        print sprintf("Call the get preferred Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/preferred-',$header);
        $response->assertStatus(404);
    }
}
