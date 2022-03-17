<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\User;

class SupportedCampsApiTest extends TestCase
{
    public function testGuestUserCanoNotAccessDirectSupportedCampList()
    {
        print sprintf("Direct supported camps list can not be accessed by guest user %d %s", 401,PHP_EOL);
       
        $response = $this->call('GET', '/api/v3/get-direct-supported-camps', []);
        $this->assertEquals(401, $response->status());       
    }


    public function testGuestUserCanoNotAccessDelegatedSupportedCampList()
    {
        print sprintf("Deleagted supported camps list can not be accessed by guest user %d %s", 401,PHP_EOL);
       
        $response = $this->call('GET', '/api/v3/get-delegated-supported-camps', []);
        $this->assertEquals(401, $response->status());       
    }
}
