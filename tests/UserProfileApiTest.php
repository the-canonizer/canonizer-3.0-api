<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserProfileApiTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGetuserProfileWithEmptyParam()
    {
        print sprintf("Test with invalid parameter passed");
        $response = $this->call('GET', '/api/v3/user/supports', []);
        $this->assertEquals(404, $response->status());
    }

    public function testGetuserProfileWithValidParam()
    {
        print sprintf("Test with invalid parameter passed");
        $response = $this->call('GET', '/api/v3/user/supports/1?topicnum=&campnum&namespace=', []);
        $this->assertEquals(200, $response->status());
    }
}
