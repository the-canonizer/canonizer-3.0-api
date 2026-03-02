<?php

namespace Tests;

use App\Models\Reply;
use Laravel\Lumen\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostApiTest extends TestCase
{

    use WithoutMiddleware;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testPostStoreValidateFiled()
    {
        $rules = [
            'body' => 'required',
            'nick_name' => 'required',
            'camp_num' => 'required',
            'topic_num' => 'required',
            'topic_name' => 'required',
            'thread_id' => 'required',
        ];

        $data = [
            "body" => "gfgfgfffefef",
            "nick_name" => "449",
            "thread_id" => "465",
            "camp_num" => "1",
            "topic_num" => "290",
            "topic_name" => "Saurabh singh te11s111t 142"
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testPostStoreWithInvalidData()
    {
        print sprintf(" \n Invalid Post Store details submitted %d %s", 400, PHP_EOL);

        $Post = Reply::factory()->make();
        $parameter = [
            "body" => "",
            "nick_name" => "",
            "thread_id" => "",
            "camp_num" => "",
            "topic_num" => "",
            "topic_name" => ""
        ];

        $_res = $this->actingAs($Post)->post('/api/v3/post/save', $parameter);
        $_res->assertStatus(400);
    }

    public function testPostStoreWithValidData()
    {
        print sprintf(" \n Valid Post Store details submitted %d %s", 200, PHP_EOL);

        $rand = rand(10, 99);
        $parameters = [
            "body" => "gfgfgfffefef bhb". $rand,
            "nick_name" => "449",
            "thread_id" => "465",
            "camp_num" => "1",
            "topic_num" => "290",
            "topic_name" => "Saurabh singh te11s111t 142"
        ];
        $_res = $this->call('POST', '/api/v3/post/save', $parameters);

        //dd($_res);
        $_res->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }

    public function testGetPostListInvalidData()
    {
        print sprintf("\n Get Post List Invalid Data %d %s", 404, PHP_EOL);
       $Post = Reply::factory()->make();

        $this->actingAs($Post)
            ->get('/api/v3/post/list?page=1&per_page=10&like=');
        $_res->assertStatus(404);
    }

    public function testGetPostListValidData()
    {
        print sprintf(" \n  Get Post List Valid Data %d %s", 200, PHP_EOL);
        $Post = Reply::factory()->make();

        $this->actingAs($Post)
            ->get('/api/v3/post/list/465?page=1&per_page=10&like=');
        $_res->assertStatus(200);
    }

    public function testPostUpdateInvalidData()
    {
        print sprintf("\n Get Post Update Invalid Data %d %s", 400, PHP_EOL);
        $response = $this->call('PUT', '/api/v3/post/update/465');
        $_res->assertStatus(400);
    }

    public function testPostUpdateValidData()
    {
        print sprintf(" \n  Get Post Update Valid Data %d %s", 200, PHP_EOL);
        $Post = Reply::factory()->make();
        $parameters = [
            "body" => "gfgfgfffefef",
            "nick_name" => "449",
            "thread_id" => "465",
            "camp_num" => "1",
            "topic_num" => "290",
            "topic_name" => "Saurabh singh te11s111t 142"
        ];
        $this->actingAs($Post)
            ->put('/api/v3/post/update/465', $parameters);
        $_res->assertStatus(200);
    }
}
