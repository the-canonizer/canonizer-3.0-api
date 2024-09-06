<?php

use App\Models\Camp;
use App\Models\Thread;
use App\Models\Topic;
use App\Models\User;
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
            'topic_name' => 'required|max:80|unique:topic|regex:/^[a-zA-Z0-9\s]+$/',
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
        print sprintf(" \n Invalid Topic Store details submitted %d %s", 400,PHP_EOL);

        $topic = Topic::factory()->make();
        $parameter = [
            'topic_name' => '',
            'namespace' => '',
            'create_namespace' => '',
            'nick_name' => '',
            'asof' => ''
        ];

        $this->actingAs($topic)->post('/api/v3/topic/save', $parameter);
        $this->assertEquals(400, $this->response->status());
    }

    public function testTopicStoreWithValidData()
    {
        print sprintf(" \n Valid Topic Store details submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $parameters = [
            'topic_name' => 'test'. rand(10, 99),
            'namespace'=>'16',
            'nick_name'=>'347',
        ];

        $this->actingAs($user)->post('/api/v3/topic/save', $parameters);
        $this->assertEquals(200, $this->response->status());

        // After topic creation , check the camp leader is added by default...
        if($this->response->status() == 200) {
            $checkCampLeader = Camp::where("topic_num", $this->response->json()["data"]["topic_num"])
                                ->where("camp_num", 1)->first();

            if(!empty($checkCampLeader)) {
                $this->assertEquals($parameters["nick_name"], $checkCampLeader?->camp_leader_nick_id);
            }
        }
    }
   
}
