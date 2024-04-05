<?php

use App\Models\User;


class GetAgreeSupportersListApiTest extends TestCase
{
   /**
     * Check Api with empty form data
     * validation
     */
    public function testAgreeSupportersApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data \n");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-change-supporters', [] ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testAgreeSupportersApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num"=> "",
            "camp_num"=> "",
            "change_id"=> 0,
            "type"=> ""
        ];
        print sprintf("Test with empty values \n");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-change-supporters', $emptyData ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with False values
     * validation
     */
    public function testAgreeSupportersApiWithFalseData()
    {
        $emptyData = [
            "topic_num"=> "2222222222",
            "camp_num"=> "1232312",
            "change_id"=> 3508,
            "type"=> "statemesnt"
        ];
        print sprintf("Test with id that dose not exist \n");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-change-supporters', $emptyData ,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }
}
