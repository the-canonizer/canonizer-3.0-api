<?php

use App\Models\User;

class CampStatementApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetStatementApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-statement', [], $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetStatementApiWithEmptyValues()
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
        $this->actingAs($user)->post('/api/v3/get-camp-statement', $emptyData, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with Invalid as_of filter value
     * validation
     */
    public function testGetStatementApiWithInvalidData()
    {
        $invalidData = [
            'topic_num' => 279,
            'camp_num' => 4,
            'as_of' => "xyz"
        ];
        print sprintf("Test with invalid values");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-statement', $invalidData, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetCampStatementApiWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => 279,
            'camp_num' => 4,
            'as_of' => "bydate"
        ];

        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-statement', $invalidData, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }
    /**
     * Check Api response code with valid data
     */
    public function testGetStatementApiStatus()
    {
        $data = [
            'topic_num' => 279,
            'camp_num' => 4,
            'as_of' => "default"
        ];
        print sprintf("\n post Camp Statement ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-statement', $data, $header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetStatementApiResponse()
    {
        $data = [
            'topic_num' => 279,
            'camp_num' => 4,
            'as_of' => "default"
        ];
        print sprintf("\n Test  Camp Statement API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-statement', $data, $header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    public function testGetStatementApiNotFound()
    {
        $data = [
            "topic_num" => 123456,
            "camp_num" => 2,
            "as_of" => "default",
            "as_of_date" => 1697007755.398
        ];
        print sprintf("\n Test  Camp Statement API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-statement', $data, $header);
        //  dd($this->response);
        $this->assertEquals(404, $this->response->status());
    }
}
