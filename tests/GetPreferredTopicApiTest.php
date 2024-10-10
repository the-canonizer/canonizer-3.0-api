<?php

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
        $this->actingAs($user)->get('/api/v3/preferred-topic',$header);
        $this->assertEquals(200, $this->response->status());
    }
    public function testGetPreferredTopicApiWithInvalidMetgod()
    {
        print sprintf("Call the get preferred Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/preferred-topic',$header);
        $this->assertEquals(405, $this->response->status());
    }
    public function testGetPreferredTopicApiWithInvalidURL()
    {
        print sprintf("Call the get preferred Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/preferred-',$header);
        $this->assertEquals(404, $this->response->status());
    }
}
