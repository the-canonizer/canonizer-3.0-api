<?php

use App\Models\Topic;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopicStoreApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testTopicStoreValidateFiled()
    {
        $rules = [
            'topic_name' => 'required|max:30|unique:topic|regex:/^[a-zA-Z0-9\s]+$/',
            'namespace' => 'required',
            'create_namespace' => 'required_if:namespace,other|max:100',
            'nick_name' => 'required',
            'asof' => 'in:default,review,bydate'
        ];
        
        $data = [
            'topic_name' => 'Test 1234 Test',
            'namespace' => '12',
            'create_namespace' => '',
            'nick_name' => '12',
            'asof' => ''
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testTopicStoreWithInvalidData(){
        print sprintf(" \n Invalid Topic Store details submitted %d %s", 401,PHP_EOL);

        $parameter = [
            'topic_name' => '',
            'namespace' => '',
            'create_namespace' => '',
            'nick_name' => '',
            'asof' => ''
        ];

        $response = $this->call('POST', '/api/v3/topic/save', $parameter);
        $this->assertEquals(401, $response->status());

        // $this->actingAs($Topic)
        // ->post('/api/v3/topic/save', $parameter);
        
        // $this->assertEquals(400, $this->response->status());
    }

    public function testTopicStoreWithValidData()
    {
        print sprintf(" \n Valid Topic Store details submitted %d %s", 200,PHP_EOL);
        $parameters = [
            'topic_name' => 'Test 1234 Test',
            'namespace' => '12',
            'create_namespace' => '',
            'nick_name' => '12',
            'asof' => ''
        ];

        $this->call('POST', '/api/v3/topic/save', $parameters);
       // dd($this->response); die;
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                'topic_num'
            ]
        ]);
        // $this->actingAs($Topic)
        //     ->post('/api/v3/topic/save',$parameters);   

        // $this->assertEquals(200, $this->response->status());
    }
   
}
