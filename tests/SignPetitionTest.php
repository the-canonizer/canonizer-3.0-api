<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
        $response = $this->actingAs($user)->post('/api/v3/camp/sign', [], $header);
        //  dd($response);
        $response->assertStatus(400);
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
        $response = $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($response);
        $response->assertStatus(400);
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
        $response = $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($response);
        $response->assertStatus(403);
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
        $response = $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($response);
        $response->assertStatus(400);


        // Missing camp_num
        $payload = [
            "nick_name_id" => 677,
            "topic_num" => 279
        ];
        
        $response = $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($response);
        $response->assertStatus(400);


        // Missing nick_name_id
        $payload = [
            "topic_num" => 279,
            "camp_num" => 1
        ];
        
        $response = $this->actingAs($user)->post('/api/v3/camp/sign', $payload, $header);
        //  dd($response);
        $response->assertStatus(400);
    }
}
