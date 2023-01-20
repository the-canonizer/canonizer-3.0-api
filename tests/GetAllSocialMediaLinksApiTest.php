<?php

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
        $this->actingAs($user)->get('/api/v3/get-social-media-links',$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}
