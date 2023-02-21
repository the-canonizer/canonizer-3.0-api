<?php

use App\Models\User;

class GetNewsFeedApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetNewsFeedApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-newsfeed', [] ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetNewsFeedApiWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => ''
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-newsfeed', $emptyData ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api response code with valid data
     */
    public function testGetNewsFeedApiStatus()
    {
        $data = [
            'topic_num' => 2,
            'camp_num' => 1
        ];    
        print sprintf("\n post NewsFeed ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-newsfeed', $data ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetNewsFeedApiResponse() 
    {
        $data = [
            'topic_num' => 200,
            'camp_num' => 1
        ]; 
        print sprintf("\n Test News Feed API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-newsfeed', $data ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}
