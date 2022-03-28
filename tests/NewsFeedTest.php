<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class NewsFeedTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNewsFeedAPIValidateFileds()
    {
        
        $rules = [
            'topic_num' => 'required',
            'camp_num' => 'required',
        ];

        $data = [
            'topic_num' => 1,
            'camp_num' => 1,
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testNewsFeedAPI()
    {
        print sprintf("\n Get News Feed ", 200, PHP_EOL);
        $response = $this->call('GET', '/api/v3/get/camp-newsfeed', [
            'topic_num' => 1,
            'camp_num' => 1,
        ]);
        $this->assertEquals(200, $response->status());
    }

    public function testNewsFeedAPIResponse()
    {
        print sprintf("\n Test News Feed API Response ", 200, PHP_EOL);
        $this->call('GET', '/api/v3/get/camp-newsfeed', [
            'topic_num' => 277,
            'camp_num' => 1,
        ]);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'id',
                    'display_text',
                    'link',
                    'available_for_child',
                ]
            ]
        ]);
    }
}
