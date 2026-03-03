<?php

namespace Tests;

use App\Models\User;

class GetStatementHistoryTest extends TestCase
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
        $this->camp = \App\Models\Camp::factory()->create(['topic_num' => $this->topic->topic_num, 'camp_num' => 1, 'submitter_nick_id' => $this->nickname->id]);
        \App\Models\Statement::factory()->create(['topic_num' => $this->topic->topic_num, 'camp_num' => $this->camp->camp_num, 'submitter_nick_id' => $this->nickname->id]);
    }

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetStatementHistoryApiWithEmptyFormData()
    {
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-statement-history', [] ,$header);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testGetStatementHistoryApiWithEmptyValues()
    {
        $emptyData = [
            "per_page" => "",
            "page" => "",
            "topic_num" => "",
            "camp_num" => ""
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-statement-history', $emptyData ,$header);
        $response->assertStatus(400);
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testGetStatementHistoryApiWithValidData()
    {
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-statement-history', $validData ,$header);
        $response->assertStatus(200);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testGetStatementHistoryApiWithInvalidData()
    {
        $invalidData = [
            "per_page" => "10",
            "page" => "1",
            "type" => "invalid",
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
        ];
        print sprintf("Test with invalid values");
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-statement-history', $invalidData ,$header);
        $response->assertStatus(400);
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testGetStatementHistoryApiWithoutUserAuth()
    {
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-statement-history', $validData ,$header);
        $response->assertStatus(200);
    }

    /**
     * Check Api response structure
     */
    public function testGetStatementHistoryApiResponse() 
    {
        $data = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => $this->topic->topic_num,
            "type" => "all",
            "camp_num" => $this->camp->camp_num
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-statement-history', $data ,$header);
        $response->assertStatus(200);
    }

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetCampStatementHistoryApiWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => $this->topic->topic_num,
            'camp_num' => $this->camp->camp_num,
            "type" => "all",
            'as_of' => "bydate"
        ];

        print sprintf("Test with invalid values");
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-statement-history', $invalidData ,$header);
        $response->assertStatus(400);
    } 
    
    public function testGetCampStatementHistoryApiNotFound()
    {
        $data = [
            "topic_num" => 12345,
            "camp_num" => 2,
            "type" => "all",
            "per_page" => 4,
            "page" => 1
        ];

        print sprintf("Test if camp statement not found");
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-statement-history', $data ,$header);
        $response->assertStatus(404);
    }

}
