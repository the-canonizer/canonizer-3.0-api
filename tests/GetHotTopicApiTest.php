<?php

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
        $this->actingAs($user)->get('/api/v3/hot-topic',$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
    public function testGetHotTopicApiWithInvalidMetgod()
    {
        print sprintf("Call the get Hot Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/hot-topic',$header);
        $this->assertEquals(405, $this->response->status());
    }
    public function testGetHotTopicApiWithInvalidURL()
    {
        print sprintf("Call the get Hot Topic Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/hot-',$header);
        $this->assertEquals(404, $this->response->status());
    }
}
