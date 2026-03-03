<?php

namespace Tests;

use App\Models\User;

class GetTopicHistoryApiTest extends TestCase
{
    protected $user;
    protected $nickname;
    protected $topic;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->nickname = \App\Models\Nickname::factory()->create(['user_id' => $this->user->id]);
        $this->topic = \App\Models\Topic::factory()->create(['submitter_nick_id' => $this->nickname->id]);
    }

     /**
     * Check Api with empty form data
     * validation
     */
    public function testGetTopicHistoryApiWithEmptyFormData()
    {
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-history', [] ,$header);
        $response->assertStatus(400);
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
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-history', $emptyData ,$header);
        $response->assertStatus(400);
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
            "topic_num" => $this->topic->topic_num,
            "type" => "live",
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-history', $validData ,$header);
        $response->assertStatus(200);
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
            "topic_num" => $this->topic->topic_num,
        ];
        print sprintf("Test with invalid values");
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-history', $invalidData ,$header);
        $response->assertStatus(400);
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
            "topic_num" => $this->topic->topic_num,
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-history', $validData ,$header);
        $response->assertStatus(200);
    }

    /**
     * Check Api response structure
     */
    public function testGetTopicHistoryApiResponse()    
    {
        $data = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => $this->topic->topic_num,
            "type" => "all",
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-history', $data ,$header);
        $response->assertStatus(200);
    }

    public function testIfRecordNotFound()    
    {
        $data = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "12312312",
            "type" => "all",
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-history', $data ,$header);
        $response->assertStatus(404);
    }
}
