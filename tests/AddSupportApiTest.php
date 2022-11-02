<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class AddSupportApiTest extends TestCase
{

    use DatabaseTransactions;
    
    
  
    public function testAddSupportMessageWhenSupportDoNotExists()
    {
        print sprintf(" \n check if support exists Api... %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make();
        $this->actingAs($user)->get('/api/v3/support/check?topic_num=715&camp_num=1');
        
        $this->seeJsonEquals([
            'status_code'=>200,
            'error'=>"",
            'message'=>"This camp doesn't have your support",
            "data" => [
                'support_flag' => 0,
            ],
        ]);
    }

    public function testWarningMessageIFSupportExistsButNoWarningMessage()
    {
        print sprintf(" \n Support Exists in topic and adding support in siblings and managing support should return no warning message.  %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);
        
        $this->actingAs($user)->get('/api/v3/support/check?topic_num=173&camp_num=3');
        
        $this->seeJsonEquals([
            'status_code'=>200,
            'error'=>"",
            'message'=>"This camp is already supported",
            "data" => [
                'support_flag' => 1,
                "camp_num"=>"3",
                "is_confirm"=>0,
                "topic_num"=>"173"
            ],
        ]);
    }

    

    public function testWarningMessageIfSupportSwitchFromChildToParent()
    {
        print sprintf(" \n Warning Message appears when support exists in child and now switching to parent  %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);
        
        $this->actingAs($user)->get('/api/v3/support/check?topic_num=173&camp_num=1');

        $response = [
            "status_code"=>200,
            "message"=> "This camp doesn't have your support",
            "error"=> "",
            "data"=>[
                "is_confirm"=> 1,
                "warning"=>"\"Agreement\" is a parent camp to \"Types Of Testing\", so if you commit support to \"Agreement\", the support of the child camp \"Types Of Testing\" will be removed.",
                "topic_num"=> "173",
                "camp_num"=> "1",
                "support_flag"=> 0
            ]
        ];

       
        $this->seeJsonEquals($response);
    }

    
    public function testWarningMessageIfSupportSwitchedFromParentToChild()
    {
        print sprintf(" \n Warning appear when support exists in parent and now switching to child  %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);
        
        $this->actingAs($user)->get('/api/v3/support/check?topic_num=192&camp_num=2');

        $response = [
                "data" => [
                        "camp_num"=>"2",
                        "is_confirm"=>1,
                        "support_flag"=>0,
                        "topic_num"=>"192",
                        "warnng"=>"testtesttesttesttesttes\" is a child camp to \"Agreement\", so if you commit support to \"testtesttesttesttesttes\", the support of the parent camp \"Agreement\" will be removed."
                        ],
                "error"=>"",
                "message"=>"This camp doesn't have your support",
                "status_code"=>200
            ];

       
        $this->seeJsonEquals($response);
    }


    
    /*
    public function testWarningMessageWhenDelgatorSupporterAddDirectSupport()
    {
        print sprintf(" \n Warning appear when delegate support exists in topic and now adding direct support %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '413',
        ]);

        $this->actingAs($user)->get('/api/v3/support/check?topic_num=267&camp_num=2');

        $response = [
                "status_code"=> 200,
                "message"=>"This camp is already supported",
                "error"=> "",
                "data" => [
                    "warning"=> "You have delegated your support to user Rupali C in this camp. If you continue your delegated support will be removed.",
                    "is_delegator"=> 1,
                    "topic_num"=> "267",
                    "camp_num"=> "2",
                    "is_confirm"=> 1,
                    "support_flag"=> 1
                ]
            ];

        $this->seeJsonEquals($response);
    }  

  
    public function testAddSupportWithValidData()
    {
        print sprintf(" \n  Add Direct Support %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '413',
        ]);
        $data = [
            "topic_num" => 435,
            "add_camp" => 
                    [
                        "camp_num" => 2,
                        "support_order" => 1
                    ],
            "remove_camps" => [],
            "type" => "direct",
            "action" => "add",
            "nick_name_id" => 357,
            "order_update" => []
        ];

        $this->actingAs($user)->post('/api/v3/support/add', $data);
        $this->assertEquals(200, $this->response->status());
    }*/
}
