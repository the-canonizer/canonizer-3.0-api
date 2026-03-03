<?php

namespace Tests;

use App\Models\User;

class GetWhatNewContentApiTest extends TestCase
{

    public function testGetWhatNewContentApi()
    {
        print sprintf("Call the GetWhatNewContent Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->get('/api/v3/get-whats-new-content',$header);
        //  dd($response);
        $response->assertStatus(200);
    }
}
