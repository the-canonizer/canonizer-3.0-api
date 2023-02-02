<?php

use App\Models\User;
class GetAllNamespacesApiTest extends TestCase
{
    
    public function testGetAllNamespacesApi()
    {
        print sprintf("Call the GetAllNamespaces Api");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->get('/api/v3/get-all-namespaces',$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}
