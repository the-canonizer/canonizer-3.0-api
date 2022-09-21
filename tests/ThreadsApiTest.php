<?php

use App\Models\Camp;
use App\Models\Thread;
use Laravel\Lumen\Testing\WithoutMiddleware;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThreadsApiTest extends TestCase
{

    use WithoutMiddleware;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testThreadStoreValidateFiled()
    {
        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';

        $rules = [
            'title'    => 'required|max:100|regex:/^[a-zA-Z0-9\s]+$/',
            'nick_name' => 'required',
            'camp_num' => 'required',
            'topic_num' => 'required',
            'topic_name' => 'required',
        ];

        $data = [
            "title" => "Test 3",
            "nick_name" => "449",
            "camp_num" => "1",
            "topic_num" => "290",
            "topic_name" => "Saurabh singh te11s111t 142"
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testThreadStoreWithInvalidData()
    {
        print sprintf(" \n Invalid Thread Store details submitted %d %s", 400, PHP_EOL);

        $Thread = Thread::factory()->make();
        $parameter = [
            "title" => "",
            "nick_name" => "",
            "camp_num" => "",
            "topic_num" => "",
            "topic_name" => ""
        ];

        $this->actingAs($Thread)->post('/api/v3/thread/save', $parameter);
        $this->assertEquals(400, $this->response->status());
    }

    public function testThreadStoreWithValidData()
    {
        print sprintf(" \n Valid Thread Store details submitted %d %s", 200, PHP_EOL);

        $rand = rand(10, 99);
        $parameters = [
            "title" => "Test 3". $rand,
            "nick_name" => "449",
            "camp_num" => "1",
            "topic_num" => "290",
            "topic_name" => "Saurabh singh te11s111t 142"
        ];
        $this->call('POST', '/api/v3/thread/save', $parameters);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }

    public function testGetThreadListInvalidData(){
        print sprintf("\n Get Thread List Invalid Data %d %s",400, PHP_EOL);
        $response = $this->call('GET', '/api/v3/thread/list');
        $this->assertEquals(400, $response->status()); 
    }

    public function testGetThreadListValidData(){
        print sprintf(" \n  Get Thread List Valid Data %d %s", 200,PHP_EOL);
        $Thread = Thread::factory()->make();

        $this->actingAs($Thread)
        ->get('/api/v3/thread/list?camp_num=4&topic_num=3&type=all');
        $this->assertEquals(200, $this->response->status());
    }

    public function testThreadUpdateInvalidData(){
        print sprintf("\n Get Thread Update Invalid Data %d %s",400, PHP_EOL);
        $response = $this->call('PUT', '/api/v3/thread/update/465');
        $this->assertEquals(400, $response->status()); 
    }

    public function testThreadUpdateValidData(){
        print sprintf(" \n  Get Thread Update Valid Data %d %s", 200,PHP_EOL);
        $Thread = Thread::factory()->make();
        $rand = rand(10, 99);
        $parameters = [
            "title" => "Test 3". $rand,
            "nick_name" => "449",
            "camp_num" => "1",
            "topic_num" => "290",
            "topic_name" => "Saurabh singh te11s111t 142"
        ];
        $this->actingAs($Thread)
        ->put('/api/v3/thread/update/51', $parameters);
       // dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}
