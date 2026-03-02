<?php

namespace Tests;

use App\Models\User;

class GetCampHistoryApiTest extends TestCase
{
     /**
     * Check Api with empty form data
     * validation
     */
    public function testGetTopicHistoryApiWithEmptyFormData()
    {
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-camp-history', [] ,$header);
        //  dd($_res);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testGetTopicHistoryApiWithEmptyValues()
    {
        $emptyData = [
            "per_page" => "",
            "page" => "",
            "topic_num" => "",
            "type" => "",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-camp-history', $emptyData ,$header);
        //  dd($_res);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testGetTopicHistoryApiWithValidData()
    {
        $validData = [
            "topic_num" => "45",
            "camp_num" => "1",
            "type" => "live",
            "page" => "1",
            "per_page" => "10",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-camp-history', $validData ,$header);
        //  dd($_res);
        $_res->assertStatus(200);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testGetTopicHistoryApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => "45",
            "camp_num" => "1",
            "per_page" => "10",
            "page" => "1",
            "type" => "invalid",
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-camp-history', $invalidData ,$header);
        //  dd($_res);
        $_res->assertStatus(400);
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testGetTopicHistoryApiWithoutUserAuth()
    {
        $validData = [
            "topic_num" => "45",
            "camp_num" => "1",
            "per_page" => "10",
            "page" => "1",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-camp-history', $validData ,$header);
        //  dd($_res);
        $_res->assertStatus(200);
    }

    /**
     * Check Api response structure
     */
    public function testGetTopicHistoryApiResponse()    
    {
        $data = [
            "topic_num" => "45",
            "camp_num" => "1",
            "type" => "all",
            "per_page" => "10",
            "page" => "1",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-camp-history', $data ,$header);
        //  dd($_res);
        $_res->assertStatus(200);
    }

    public function testIfRecordNotFound()    
    {
        $data = [
            "topic_num" => "123123",
            "camp_num" => "1",
            "type" => "all",
            "per_page" => "10",
            "page" => "1",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-camp-history', $data ,$header);
        $_res->assertStatus(404);

        $data = [
            "topic_num" => "45",
            "camp_num" => "121231",
            "type" => "all",
            "per_page" => "10",
            "page" => "1",
        ];

        $_res = $this->actingAs($user)->post('/api/v3/get-camp-history', $data ,$header);
        $_res->assertStatus(404);
    }
}
