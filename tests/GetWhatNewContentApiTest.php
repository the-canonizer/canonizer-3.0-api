<?php

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
        $this->actingAs($user)->get('/api/v3/get-whats-new-content',$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}
