<?php

use App\Models\Camp;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Lumen\Testing\WithoutMiddleware;

class CampStoreApiTest extends TestCase
{

    use WithoutMiddleware;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testCampStoreValidateFiled()
    {
        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';

        $rules = [
            'nick_name' => 'required',
            'camp_name' => 'required|unique:camp|max:30|regex:/^[a-zA-Z0-9\s]+$/',
            'camp_about_url' => 'nullable|max:1024|regex:' . $regex,
            'parent_camp_num' => 'nullable',
            'asof' => 'in:default,review,bydate'
        ];

        $data = [
            'nick_name' => '12',
            'camp_name' => 'Test 1234 Test',
            'camp_about_url' => '',
            'parent_camp_num' => '12',
            'asof' => ''
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testCampStoreWithInvalidData()
    {
        print sprintf(" \n Invalid Topic Store details submitted %d %s", 400, PHP_EOL);

        $camp = Camp::factory()->make();
        $parameter = [
            'nick_name' => '',
            'camp_name' => '',
            'camp_about_url' => '',
            'parent_camp_num' => '',
            'asof' => ''
        ];

        $this->actingAs($camp)->post('/api/v3/camp/save', $parameter);
        $this->assertEquals(400, $this->response->status());
    }

    public function testCampStoreWithValidData()
    {
        print sprintf(" \n Valid Topic Store details submitted %d %s", 200, PHP_EOL);

        $camp = Camp::factory()->make();
        $rand = rand(10, 99);
        $parameters = [
            "camp_name" => "Saurabh sing11h " . $rand,
            "parent_camp_num" => (string) $rand,
            "topic_num" => (string) $rand,
            "nick_name" => (string) $rand,
            "camp_about_url" => "",
            "asof"=>""
        ];
        $this->call('POST', '/api/v3/camp/save', $parameters);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
            ]
        ]);
    }
}
