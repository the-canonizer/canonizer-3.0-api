<?php

namespace Tests;

use App\Models\Camp;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class GetUsedTopicNickNameApiTest extends TestCase
{

    use WithoutMiddleware;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testGetUsedTopicNickNameValidateFiled()
    {
        $rules = [
            'topic_num' => 'required',
        ];
        $rand = rand(10, 99);
        $data = [
            'topic_num' => $rand
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testGetUsedTopicNickNameWithInvalidData()
    {
        print sprintf(" \n Invalid Get Used Topic Nick Name details submitted %d %s", 400, PHP_EOL);

        $camp = Camp::factory()->make();
        $parameter = [
            'topic_num' => '',
        ];

        $_res = $this->actingAs($camp)->post('/api/v3/camp/get-topic-nickname-used', $parameter);
        $_res->assertStatus(400);
    }

    public function testGetUsedTopicNickNameWithValidData()
    {
        print sprintf(" \n Valid Get Used Topic Nick Name details submitted %d %s", 200, PHP_EOL);

        $camp = Camp::factory()->make();
        $rand = rand(10, 99);
        $parameters = [
            "topic_num" => (string) $rand,
        ];
        $_res = $this->call('POST', '/api/v3/camp/get-topic-nickname-used', $parameters);
        $_res->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
            ]
        ]);
    }
}
