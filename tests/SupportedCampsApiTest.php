<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;

class SupportedCampsApiTest extends TestCase
{
    public function testGuestUserCanoNotAccessDirectSupportedCampList()
    {
        print sprintf("Direct supported camps list can not be accessed by guest user %d %s", 401,PHP_EOL);
       
        $response = $this->getJson('/api/v3/get-direct-supported-camps');
        $response->assertStatus(401);       
    }


    public function testGuestUserCanoNotAccessDelegatedSupportedCampList()
    {
        print sprintf("Deleagted supported camps list can not be accessed by guest user %d %s", 401,PHP_EOL);
       
        $response = $this->getJson('/api/v3/get-delegated-supported-camps');
        $response->assertStatus(401);       
    }
}
