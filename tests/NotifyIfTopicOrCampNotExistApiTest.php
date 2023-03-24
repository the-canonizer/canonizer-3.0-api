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
            'topic_num' => 'required_if:is_type,topic|required_if:is_type,statement|numeric|gt:0',
            'camp_num' => 'required_if:is_type,topic|required_if:is_type,statement|numeric|gt:0',
            'nick_id' => 'required_if:is_type,nickname|numeric|gt:0',
            'thread_id' => 'required_if:is_type,thread|numeric|gt:0',
            'url' => 'required',
        ];

        $data = [
            "topic_num" => "80",
            "camp_num" => "1",
            "url" => "/topic.asp/120/8",
            "is_type" => "thread",
            "nick_id" => "",
            "thread_id" => "514"
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testNotifyIfTopicOrCampNotExistWithInvalidData()
    {
        print sprintf(" \n  Notify If Topic Or Camp Not Exist with Invalid details submitted %d %s", 400, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/notify-if-url-not-exist', [], $header);
        $this->assertEquals(400, $this->response->status());
    }

    public function testNotifyIfTopicOrCampNotExistWithValidData()
    {
        print sprintf(" \n  Notify If Topic Or Camp Not Exist with Valid details submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $data = [
            "topic_num" => "80",
            "camp_num" => "1",
            "url" => "/topic.asp/120/8",
            "is_type" => "thread",
            "nick_id" => "",
            "thread_id" => "514"
        ];
        $this->actingAs($user)->post('/api/v3/notify-if-url-not-exist', $data, $header);
        $this->assertEquals(200, $this->response->status());
    }
    public function testNotifyIfStatementNotExistWithValidData()
    {
        print sprintf(" \n  Notify If Statement Not Exist with Valid details submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $data = [
            "topic_num" => "80",
            "camp_num" => "1",
            "url" => "/topic.asp/120/8",
            "is_type" => "statement",
            "nick_id" => "",
            "thread_id" => ""
        ];
        $this->actingAs($user)->post('/api/v3/notify-if-url-not-exist', $data, $header);
        $this->assertEquals(200, $this->response->status());
    }
    public function testNotifyIfThreadNotExistWithValidData()
    {
        print sprintf(" \n  Notify If Thread Not Exist with Valid details submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $data = [
            "topic_num" => "",
            "camp_num" => "",
            "url" => "/thread.asp/88/1/514",
            "is_type" => "thread",
            "nick_id" => "",
            "thread_id" => "514"
        ];
        $this->actingAs($user)->post('/api/v3/notify-if-url-not-exist', $data, $header);
        $this->assertEquals(200, $this->response->status());
    }
    public function testNotifyIfNicknameNotExistWithValidData()
    {
        print sprintf(" \n  Notify If Nickname Not Exist with Valid details submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $data = [
            "topic_num" => "",
            "camp_num" => "",
            "url" => "/support_list.asp?nick_name_id=1",
            "is_type" => "nickname",
            "nick_id" => "1",
            "thread_id" => ""
        ];
        $this->actingAs($user)->post('/api/v3/notify-if-url-not-exist', $data, $header);
        $this->assertEquals(200, $this->response->status());
    }
}
