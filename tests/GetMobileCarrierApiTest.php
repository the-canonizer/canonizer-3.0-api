<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class GetMobileCarrierApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */
  

    public function testGetMobileCarrierApi()
    {   
        print sprintf("\n Get mobile carrier ",200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->get('/api/v3/mobile-carrier',$header);
        //   dd($this->response);
        $this->assertEquals(200, $this->response->status());
        
    }
}