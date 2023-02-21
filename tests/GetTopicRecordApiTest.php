<?php

use App\Models\User;

class GetTopicRecordApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetTopicRecordApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', [] ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
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
            'as_of' => ''
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $emptyData ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with Invalid as_of filter value
     * validation
     */
    public function testGetTopicRecordApiWithInvalidData()
    {
        $invalidData = [
            'topic_num' => 45,
            'camp_num' => 1,
            'as_of' => "xyz",
            'as_of_date' => "12-12-2022"
        ];

        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $invalidData ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetTopicRecordApiWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => 45,
            'camp_num' => 1,
            'as_of' => "bydate"
        ];

        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $invalidData ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api response code with correct data
     */
    public function testGetTopicRecordApiStatus() 
    {
        $data = [
            'topic_num' => 45,
            'camp_num' => 1,
            'as_of' => 'default'
        ];
        print sprintf("\n get topic record ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $data ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetTopicRecordApiResponse()
    {
        $data = [
            'topic_num' => 45,
            'camp_num' => 1,
            'as_of' => 'default'
        ];

        print sprintf("\n Test GetTopicRecord API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-topic-record', $data ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}
