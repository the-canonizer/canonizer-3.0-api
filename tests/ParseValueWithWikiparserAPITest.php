<?php

use App\Models\User;

class ParseValueWithWikiparserAPITest extends TestCase
{
    public function testParseValueWithWikiparserAPIWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        // $response = $this->call('POST', '/api/v3/parse-camp-statement', []);

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/parse-camp-statement',[],$header);
        $this->assertEquals(400, $this->response->status());
    }
    
    /**
     * Check Api with empty values
     * validation
     */
    public function testParseValueWithWikiparserAPIWithEmptyValues()
    {
        $emptyData = [
            'value' => '',
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/parse-camp-statement',$emptyData,$header);
        $this->assertEquals(200, $this->response->status());
    }
    

    /**
     * Check Api response code with valid data
     */
    public function testParseValueWithWikiparserAPIStatus()
    {
        $data = [
            'value' => 'string to be parsed',
        ];
        print sprintf("\n Parse value ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/parse-camp-statement',$data,$header);
        $this->assertEquals(200, $this->response->status());
    }
    
    /**
     * Check Api response structure
     */
    public function testParseValueWithWikiparserAPIResponse()
    {
        $data = [
            'value' => '',
        ];
        print sprintf("\n  Parse value API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/parse-camp-statement',$data,$header);
        $this->assertEquals(200, $this->response->status());
    }
}
