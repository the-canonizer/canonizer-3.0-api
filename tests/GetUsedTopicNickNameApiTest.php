<?php

use App\Models\Camp;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Lumen\Testing\WithoutMiddleware;

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

        $this->actingAs($camp)->post('/api/v3/camp/getTopicNickNameUsed', $parameter);
        $this->assertEquals(400, $this->response->status());
    }

    public function testGetUsedTopicNickNameWithValidData()
    {
        print sprintf(" \n Valid Get Used Topic Nick Name details submitted %d %s", 200, PHP_EOL);

        $camp = Camp::factory()->make();
        $rand = rand(10, 99);
        $parameters = [
            "topic_num" => (string) $rand,
        ];
        $this->call('POST', '/api/v3/camp/getTopicNickNameUsed', $parameters);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
            ]
        ]);
    }
}
