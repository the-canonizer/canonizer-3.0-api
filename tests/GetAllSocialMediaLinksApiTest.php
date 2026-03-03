<?php

namespace Tests;

use App\Models\User;

class GetAllSocialMediaLinksApiTest extends TestCase
{

    public function testGetAllSocialMediaLinksApi()
    {
        print sprintf("Call the GetAllSocialMediaLinks Api");
        
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->get('/api/v3/get-social-media-links',$header);
        //  dd($response);
        $response->assertStatus(200);
    }
}
