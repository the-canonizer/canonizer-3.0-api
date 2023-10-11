<?php

use App\Models\User;

class GetTopicRecordApiTest extends TestCase
{
    public function testWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $apiPayload = [];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $apiPayload ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    public function testWithEmptyValues()
    {
        $apiPayload = [
            'topic_num' => '',
            'camp_num' => '',
            'as_of' => ''
        ];
        print sprintf("\nTest with empty values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $apiPayload ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    public function testWithInvalidData()
    {
        $apiPayload = [
            'topic_num' => 45,
            'camp_num' => 1,
            'as_of' => "xyz",
            'as_of_date' => "12-12-2022"
        ];

        print sprintf("\nTest with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $apiPayload ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    public function testWithoutFilterDate()
    {
        $apiPayload = [
            'topic_num' => 45,
            'camp_num' => 1,
            'as_of' => "bydate"
        ];

        print sprintf("\nTest with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $apiPayload ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    public function testWithValidData() 
    {
        $data = [
            'topic_num' => 45,
            'camp_num' => 1,
            'as_of' => 'default'
        ];
        print sprintf("\nTest with valid data");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $data ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    public function testIfNoTopicRecordFound()
    {
        $data = [
            'topic_num' => 123123,
            'camp_num' => 1,
            'as_of' => 'default'
        ];
        print sprintf("\nTest if no topic record found");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $data ,$header);
        //  dd($this->response);
        $this->assertEquals(404, $this->response->status());
    }
}
