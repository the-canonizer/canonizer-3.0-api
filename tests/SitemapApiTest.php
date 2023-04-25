<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseTransactions;

class SitemapApiTest extends TestCase
{

    use DatabaseTransactions;

    public function testSitemapWithValidData()
    {
        print sprintf(" \n Sitemap with valid data %d %s", 200, PHP_EOL);
        $this->call('POST', '/api/v3/sitemaps');
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }

    public function testSitemapWithInValidUrl()
    {
        print sprintf(" \n Sitemap with invalid url %d %s", 404, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/sitemap');
        //  dd($this->response);
        $this->assertEquals(404, $this->response->status());
    }
}
