<?php

namespace Tests;

use App\Models\User;

class GetCampHistoryApiTest extends TestCase
{
    protected $user;
    protected $nickname;
    protected $topic;
    protected $camp;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->nickname = \App\Models\Nickname::factory()->create(['user_id' => $this->user->id]);
        $this->topic = \App\Models\Topic::factory()->create(['submitter_nick_id' => $this->nickname->id]);
        $this->camp = \App\Models\Camp::where('topic_num', $this->topic->topic_num)->where('camp_num', 1)->first();
        // Ensure the camp has parent_camp_num as null for agreement camp
        $this->camp->update(['parent_camp_num' => null, 'submitter_nick_id' => $this->nickname->id]);
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
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-history', [] ,$header);
        //  dd($response);
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
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-history', $emptyData ,$header);
        //  dd($response);
        $response->assertStatus(400);
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testGetTopicHistoryApiWithValidData()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "type" => "live",
            "page" => "1",
            "per_page" => "10",
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-history', $validData ,$header);
        echo "\nResponse: " . $response->getContent() . "\n";
        $response->assertStatus(200);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testGetTopicHistoryApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "per_page" => "10",
            "page" => "1",
            "type" => "invalid",
        ];
        print sprintf("Test with invalid values");
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-history', $invalidData ,$header);
        //  dd($response);
        $response->assertStatus(400);
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testGetCampHistoryApiWithoutUserAuth()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "per_page" => "10",
            "page" => "1",
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-history', $validData ,$header);
        //  dd($response);
        if ($response->status() != 200) {
             dump($response->getContent());
        }
        $response->assertStatus(200);
    }

    /**
     * Check Api response structure
     */
    public function testGetCampHistoryApiResponse()    
    {
        $data = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "type" => "all",
            "per_page" => "10",
            "page" => "1",
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-history', $data ,$header);
        //  dd($response);
        if ($response->status() != 200) {
             dump($response->getContent());
        }
        $response->assertStatus(200);
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
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-history', $data ,$header);
        $response->assertStatus(404);

        $data = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => "121231",
            "type" => "all",
            "per_page" => "10",
            "page" => "1",
        ];

        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-history', $data ,$header);
        $response->assertStatus(404);
    }
}
