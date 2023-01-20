<?php

use App\Models\User;

class GetCampStatementHistoryTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampStatementHistoryApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-statement-history', [] ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetCampStatementHistoryApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "camp_num" => "",
            "type" => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-statement-history', $emptyData ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with False values
     * validation
     */
    public function testGetCampStatementHistoryApiWithFalseData()
    {
        $emptyData = [
            "topic_num" => "abc",
            "camp_num" => "abc",
            "type" => "xyz"
        ];
        print sprintf("Test with id that dose not exist");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-statement-history', $emptyData ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api response code with correct data
     */
    public function testGetCampStatementHistoryApiStatus()   
    {
        $data = [
            "topic_num" => "1",
            "camp_num" => "1",
            "type" => "all",
            "per_page" => 10,
            "page" => 1
        ];
        print sprintf("\n with correct form data ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-statement-history', $data ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

     /**
     * Check Api response structure
     */
    public function testGetCampStatementHistoryApiResponse()  
    {
        $data = [
            "topic_num" => "320",
            "camp_num" => "1",
            "type" => "all",
            "per_page" => 10,
            "page" => 1
        ];
        print sprintf("\n Test API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-statement-history', $data ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetCampStatementHistoryApiWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => 45,
            'camp_num' => 1,
            "type" => "all",
            'as_of' => "bydate"
        ];

        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-statement-history', $invalidData ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

}
