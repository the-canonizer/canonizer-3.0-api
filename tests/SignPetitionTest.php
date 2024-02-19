<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseTransactions;

class SignPetitionTest extends TestCase
{
    use DatabaseTransactions;
    
    public function testSignPetitionWithEmptyFormData()
    {
        print sprintf("\n Test with empty form data\n");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/camp/sign', [], $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }   
    
    public function testSignPetitionApiWithEmptyValues()
    {
        $payload = [
            "nick_name_id" => 0,
            "topic_num" => 0,
            "camp_num" => 0
        ];
        print sprintf("\nTest with empty values\n");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    public function testSignPetitionApiWithWrongNickname()
    {
        $payload = [
            "nick_name_id" => 677,
            "topic_num" => 279,
            "camp_num" => 1
        ];
        print sprintf("Test with wrong nickname");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($this->response);
        $this->assertEquals(403, $this->response->status());
    }

    public function testSignPetitionApiWithMissingKey()
    {
        // Missing topic_num
        $payload = [
            "nick_name_id" => 677,
            "camp_num" => 1
        ];
        print sprintf("Test with missing key");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());


        // Missing camp_num
        $payload = [
            "nick_name_id" => 677,
            "topic_num" => 279
        ];
        
        $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());


        // Missing nick_name_id
        $payload = [
            "topic_num" => 279,
            "camp_num" => 1
        ];
        
        $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }
}
