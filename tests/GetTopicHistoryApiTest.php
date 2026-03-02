<?php

namespace Tests;

use App\Models\User;

class GetTopicHistoryApiTest extends TestCase
{
     /**
     * Check Api with empty form data
     * validation
     */
    public function testGetTopicHistoryApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-topic-history', [] ,$header);
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
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-topic-history', $emptyData ,$header);
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
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "type" => "live",
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-topic-history', $validData ,$header);
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
            "per_page" => "10",
            "page" => "1",
            "type" => "invalid",
            "topic_num" => "88",
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-topic-history', $invalidData ,$header);
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
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-topic-history', $validData ,$header);
        //  dd($_res);
        $_res->assertStatus(200);
    }

    /**
     * Check Api response structure
     */
    public function testGetTopicHistoryApiResponse()    
    {
        $data = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "type" => "all",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-topic-history', $data ,$header);
        //  dd($_res);
        $_res->assertStatus(200);
    }

    public function testIfRecordNotFound()    
    {
        $data = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "12312312",
            "type" => "all",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $_res = $this->actingAs($user)->post('/api/v3/get-topic-history', $data ,$header);
        //  dd($_res);
        $_res->assertStatus(404);
    }
}
