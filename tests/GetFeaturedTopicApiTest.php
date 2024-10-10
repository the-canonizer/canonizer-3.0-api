<?php

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
        $this->actingAs($user)->get('/api/v3/featured-topic',$header);
        $this->assertEquals(200, $this->response->status());
    }
    public function testGetFeaturedTopicApiWithInvalidMetgod()
    {
        print sprintf("Call the get featured Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/featured-topic',$header);
        $this->assertEquals(405, $this->response->status());
    }
    public function testGetFeaturedTopicApiWithInvalidURL()
    {
        print sprintf("Call the get featured Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/featured-',$header);
        $this->assertEquals(404, $this->response->status());
    }
}
