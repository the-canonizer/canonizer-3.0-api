<?php

namespace Tests;

use App\Models\Camp;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;

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
            'camp_name' => 'required|unique:camp|max:80|regex:/^[a-zA-Z0-9\s]+$/',
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

        $response = $this->actingAs($camp)->post('/api/v3/camp/save', $parameter);
        $response->assertStatus(400);
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
        $response = $this->call('POST', '/api/v3/camp/save', $parameters);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
            ]
        ]);
    }
}
