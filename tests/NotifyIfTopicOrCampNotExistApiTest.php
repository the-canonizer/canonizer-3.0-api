<?php

use App\Models\Camp;
use App\Models\User;
use Laravel\Lumen\Testing\WithoutMiddleware;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotifyIfTopicOrCampNotExistApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testNotifyIfTopicOrCampNotExistValidateFiled()
    {
        $rules = [
            'topic_num' => 'required|numeric|gt:0',
            'camp_num' => 'required|numeric|gt:0',
            'url' => 'required',
        ];

        $data = [
            "topic_num" => "80",
            "camp_num" => "1123456789",
            "url" => "/topic.asp/120/8"
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testNotifyIfTopicOrCampNotExistWithInvalidData()
    {
        print sprintf(" \n  Notify If Topic Or Camp Not Exist with Invalid details submitted %d %s", 400,PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/notify-if-url-not-exist',[],$header);
        $this->assertEquals(400, $this->response->status());      
    }

    public function testNotifyIfTopicOrCampNotExistWithValidData()
    {
        print sprintf(" \n  Notify If Topic Or Camp Not Exist with Valid details submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $data = [
            "topic_num" => "80",
            "camp_num" => "1123456789",
            "url" => "/topic.asp/120/8"
        ];
        $this->actingAs($user)->post('/api/v3/notify-if-url-not-exist',$data,$header);
        $this->assertEquals(200, $this->response->status());
    }
}
