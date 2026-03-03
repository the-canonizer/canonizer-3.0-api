<?php

namespace Tests;

use App\Models\User;

class GetTopicRecordApiTest extends TestCase
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
    }

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetTopicRecordApiWithEmptyFormData()
    {
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-record', [] ,$header);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetTopicRecordApiWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => '',
            'as_of' => ""
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-record', $emptyData ,$header);
        $response->assertStatus(400);
    }

    public function testWithValidData() 
    {
        $data = [
            'topic_num' => $this->topic->topic_num,
            'camp_num' => $this->camp->camp_num,
            'as_of' => 'default'
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-record', $data ,$header);
        $response->assertStatus(200);
    }

    public function testGetTopicRecordApiWithInvalidData()
    {
        $invalidData = [
            'topic_num' => $this->topic->topic_num,
            'camp_num' => $this->camp->camp_num,
            'as_of' => "xyz"
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-topic-record', $invalidData ,$header);
        $response->assertStatus(400);
    }
}
