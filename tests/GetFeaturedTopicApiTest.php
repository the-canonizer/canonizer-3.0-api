<?php

namespace Tests;

use App\Models\User;
class GetFeaturedTopicApiTest extends TestCase
{

    public function testGetFeaturedTopicApi()
    {
        print sprintf("Call the get featured Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->get('/api/v3/featured-topic',$header);
        $_res->assertStatus(200);
    }
    public function testGetFeaturedTopicApiWithInvalidMetgod()
    {
        print sprintf("Call the get featured Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/featured-topic',$header);
        $_res->assertStatus(405);
    }
    public function testGetFeaturedTopicApiWithInvalidURL()
    {
        print sprintf("Call the get featured Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/featured-',$header);
        $_res->assertStatus(404);
    }
}
