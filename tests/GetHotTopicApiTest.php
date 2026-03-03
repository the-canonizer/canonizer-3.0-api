<?php

namespace Tests;

use App\Models\User;
class GetHotTopicApiTest extends TestCase
{
    
    public function testGetHotTopicApi()
    {
        print sprintf("Call the get Hot Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->get('/api/v3/hot-topic',$header);
        $response->assertStatus(200);
    }
    public function testGetHotTopicApiWithInvalidMetgod()
    {
        print sprintf("Call the get Hot Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/hot-topic',$header);
        $response->assertStatus(405);
    }
    public function testGetHotTopicApiWithInvalidURL()
    {
        print sprintf("Call the get Hot Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/hot-',$header);
        $response->assertStatus(404);
    }
}
