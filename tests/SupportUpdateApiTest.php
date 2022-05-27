<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class SupportUpdateApiTest extends TestCase
{
    /**
     *  [unauthorized user is restricted to perform this action]
     *
     * @return void
     */
    public function testUnauthorizedUserCannotUpdateSupportOrder(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/support-order/update', []);
        $this->assertEquals(401, $response->status());
    }

    /**
     * [unautorized user is restricted to perform this action]
     */
    public function testUnauthorizedUserCannotRemoveSupport(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/support/remove', []);
        $this->assertEquals(401, $response->status());
    }
}
