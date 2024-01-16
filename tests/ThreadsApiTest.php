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
        ->get('/api/v3/thread/list?camp_num=1&topic_num=88&type=all');
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

    public function testGetThreadByIdByWrongData() {

        // Get thread by wrong id test
        print sprintf("\n Get thread by invalid thread id %d %s",400, PHP_EOL);
        $response = $this->call('GET', '/api/v3/thread/0');
        $this->assertEquals(404, $response->status()); 

        /// with wrong id and correct topic and camp num ...
        $response = $this->call('GET', '/api/v3/thread/0?topic_num=88&camp_num=1');
        $this->assertEquals(404, $response->status()); 

        /// get thread by passing characters ...
        $response = $this->call('GET', '/api/v3/thread/esfcsefc?topic_num=88&camp_num=1');
        $this->assertEquals(404, $response->status()); 
    }

    /// with correct id and wrong topic and camp...
    public function testGetThreadByIdByWrongTopicCamp() {

        // Get thread by wrong id of topic and camp that not exist in db...
        $response = $this->call('GET', '/api/v3/thread/51?topic_num=234212&camp_num=221');
        $this->assertEquals(404, $response->status());

        // Test that thread exist in relavant topic/camp ...
        $response = $this->call('GET', '/api/v3/thread/149?topic_num=88&camp_num=1');
        $this->assertEquals(404, $response->status());
    }

    public function testGetThreadByIdByWrongKeys() {

        // Get thread by wrong id of topic and camp that not exist in db...
        $response = $this->call('GET', '/api/v3/thread/51?topc_num=234212&cam_num=221');
        $this->assertEquals(400, $response->status());
    
    }
    
    public function testGetThreadByIdValidData(){
        print sprintf(" \n  Get Thread By Id Valid Data %d %s", 200,PHP_EOL);
        $thread = Thread::factory()->make();

        $this->actingAs($thread)->get('/api/v3/thread/51?topic_num=88&camp_num=1');
        $this->assertEquals(200, $this->response->status());
    }

    public function testIfThreadRecordNotFound(){
        $thread = Thread::factory()->make();
        $this->actingAs($thread)->get('/api/v3/thread/123123123/');
        $this->assertEquals(404, $this->response->status());
    }
}
