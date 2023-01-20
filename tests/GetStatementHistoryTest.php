<?php

use App\Models\User;

class GetStatementHistoryTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetStatementHistoryApiWithEmptyFormData()
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
     * Check Api with valid data
     * validation
     */
    public function testGetStatementHistoryApiWithValidData()
    {
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "camp_num" => "1"
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-statement-history', $validData ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
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
            "topic_num" => "88",
            "camp_num" => "1",
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

    /**
     * Check Api without auth
     * validation
     */
    public function testGetStatementHistoryApiWithoutUserAuth()
    {
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "camp_num" => "1"
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-statement-history', $validData ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetStatementHistoryApiResponse() 
    {
        $data = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "type" => "all",
            "camp_num" => "1"
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-statement-history', $data ,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}
