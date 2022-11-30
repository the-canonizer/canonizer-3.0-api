<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class GetMetaTagsTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testMetaTags()
    {
        print sprintf("\n Get Meta Tags ", 200, PHP_EOL);
        $response = $this->call('POST', '/api/v3/meta-tags', []);
        $this->assertEquals(200, $response->status());
    }
}
