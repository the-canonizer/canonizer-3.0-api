<?php

use App\Models\Reply;
use Laravel\Lumen\Testing\WithoutMiddleware;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
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

        $this->actingAs($Post)->post('/api/v3/post/save', $parameter);
        $this->assertEquals(400, $this->response->status());
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
        $this->call('POST', '/api/v3/post/save', $parameters);

        //dd($this->response);
        $this->seeJsonStructure([
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
        $this->assertEquals(404, $this->response->status());
    }

    public function testGetPostListValidData()
    {
        print sprintf(" \n  Get Post List Valid Data %d %s", 200, PHP_EOL);
        $Post = Reply::factory()->make();

        $this->actingAs($Post)
            ->get('/api/v3/post/list/465?page=1&per_page=10&like=');
        $this->assertEquals(200, $this->response->status());
    }

    public function testPostUpdateInvalidData()
    {
        print sprintf("\n Get Post Update Invalid Data %d %s", 400, PHP_EOL);
        $response = $this->call('PUT', '/api/v3/post/update/465');
        $this->assertEquals(400, $response->status());
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
        $this->assertEquals(200, $this->response->status());
    }
}
