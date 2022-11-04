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
        $response = $this->call('GET', '/api/v3/mobile-carrier', []);
        $this->assertEquals(200, $response->status()); 
        
    }
}