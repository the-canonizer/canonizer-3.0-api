<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class CampStatementTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    
    public function testStatementAPIValidateFileds()
    {
        $rules = [
            'topic_num' => 'required',
            'camp_num' => 'required',
            'as_of_date' => 'required_if:asof,bydate'
        ];

        $data = [
            'topic_num' => 1,
            'camp_num' => 1,
            'as_of' => "bydate",
            'as_of_date' => "12-12-2022"
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testStatementAPI()
    {
        print sprintf("\n Get Camp Statement ", 200, PHP_EOL);
        $response = $this->call('GET', '/api/v3/get/camp-statement', [
            'topic_num' => 150,
            'camp_num' => 1,
            'as_of' => "default",
            'as_of_date' => "12-12-2022"
        ]);
        $this->assertEquals(200, $response->status());
    }

    public function testStatementAPIResponse()
    {
        print sprintf("\n Test  Camp Statement API Response ", 200, PHP_EOL);
        $response = $this->call('GET', '/api/v3/get/camp-statement', [
            'topic_num' => 150,
            'camp_num' => 1,
            'as_of' => "default",
            'as_of_date' => "12-12-2022"
        ]);

        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'id',
                    'value',
                    'note',
                ]
            ]
        ]);
    }
}
