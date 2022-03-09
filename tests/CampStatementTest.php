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
    public function testStatementAPI()
    {
        print sprintf("\n Get Camp Statement ", 200, PHP_EOL);
        $response = $this->call('GET', '/api/v3/camps-statements', [
            'topicnum' => 1,
            'parentcampnum' => 1,
            'asof' => "default",
            'asofdate' => "12-12-2022"
        ]);

        $this->assertEquals(200, $response->status());
    }

    public function testStatementAPIValidateFileds()
    {
        $rules = [
            'topicnum' => 'required',
            'parentcampnum' => 'required',
            'asofdate' => 'required_if:asof,bydate'
        ];

        $data = [
            'topicnum' => 1,
            'parentcampnum' => 1,
            'asof' => "bydate",
            'asofdate' => "12-12-2022"
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }
}
