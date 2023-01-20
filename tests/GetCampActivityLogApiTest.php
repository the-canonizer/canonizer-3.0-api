<?php

use App\Models\User;

class GetCampActivityLogApiTest extends TestCase  
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampActivityLogApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-activity-log',[],$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetCampActivityLogApiWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => ''
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-activity-log',$emptyData,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api response code with valid data
     */
    public function testGetCampActivityLogApiStatus()
    {
        $data = [
            'topic_num' => 2,
            'camp_num' => 1
        ];    
        print sprintf("\n post camp activity log", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-activity-log',$data,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetCampActivityLogApiResponse()
    {
        $data = [
            'topic_num' => 1,
            'camp_num' => 1
        ]; 
        print sprintf("\n Test camp activity log API response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-activity-log',$data,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}
