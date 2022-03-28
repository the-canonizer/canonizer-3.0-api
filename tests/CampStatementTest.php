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
    private $data = [
        'topic_num' => 95,
        'camp_num' => 5,
        'as_of' => "bydate",
        'as_of_date' => "12-12-2022"
    ];

    public function testStatementAPIValidateFileds()
    {
        $rules = [
            'topic_num' => 'required',
            'camp_num' => 'required',
            'as_of_date' => 'required_if:asof,bydate'
        ];

        $v = $this->app['validator']->make($this->data, $rules);

        $this->assertTrue($v->passes());
    }

    public function testStatementAPI()
    {
        print sprintf("\n post Camp Statement ", 200, PHP_EOL);
        $response = $this->call('post', '/api/v3/get-camp-statement', 
        $this->data
        );
        $this->assertEquals(200, $response->status());
    }

    public function testStatementAPIResponse()
    {
        print sprintf("\n Test  Camp Statement API Response ", 200, PHP_EOL);
        $this->call('post', '/api/v3/get-camp-statement', 
            $this->data
        );
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
