<?php

use App\Models\User;


class GetCampRecordApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampRecordApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-record', [], $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetCampRecordApiWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => '',
            'as_of' => "",
            'as_of_date' => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-record', $emptyData, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api response code with correct data
     */
    public function testGetCampRecordApiStatus()
    {
        $data = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "default"
        ];
        print sprintf("\n get camp record ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-record', $data, $header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api with Invalid as_of filter value
     * validation
     */
    public function testGetCampRecordApiWithInvalidData()
    {
        $invalidData = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "xyz",
            'as_of_date' => "12-12-2022"
        ];

        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-record', $invalidData, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetCampRecordApiWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "bydate"
        ];

        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-record', $invalidData, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetCampRecordApiResponse()
    {
        $data = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "default"
        ];
        print sprintf("\n Test News Feed API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-record', $data, $header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    public function testGetCampRecordApiNotFoundResponse()
    {
        $data = [
            'topic_num' => 123123,
            'camp_num' => 5,
            'as_of' => "default"
        ];
        print sprintf("\n Test News Feed API Not Found Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-record', $data, $header);
        //  dd($this->response);
        $this->assertEquals(404, $this->response->status());
    }
}
