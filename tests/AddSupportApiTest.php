<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class AddSupportApiTest extends TestCase
{

    use DatabaseTransactions;
    
    
    /***
     *  #userId used  362
     *  #ncikNameId used 347
     *  rupali.chavan9860@gmail.com
     */

    public function testAddSupportWithValidData()
    {
        print sprintf(" \n  Add Direct Support %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);
        $data = [
            "topic_num" => 189,
            "add_camp" => 
                    [
                        "camp_num" => 2,
                        "support_order" => 1
                    ],
            "remove_camps" => [],
            "type" => "direct",
            "action" => "add",
            "nick_name_id" => 347,
            "order_update" => []
        ];

        $this->actingAs($user)->post('/api/v3/support/add', $data);
        $this->assertEquals(200, $this->response->status());
    }



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

       
        $this->seeJson([
            "warning"=>"\"Agreement\" is a parent camp to \"Types Of Testing\", so if you commit support to \"Agreement\", the support of the child camp \"Types Of Testing\" will be removed.",
        ]);
    }


    public function testWarningMessageIfSupportSwitchedFromParentToChild()
    {
        print sprintf(" \n Warning appear when support exists in parent and now switching to child  %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);
        
        $this->actingAs($user)->get('/api/v3/support/check?topic_num=602&camp_num=2');


        $this->seeJson([
            "warning"=>"\"camp 1\" is a child camp to \"Agreement\", so if you commit support to \"camp 1\", the support of the parent camp \"Agreement\" will be removed."
        ]);
    }


    
   
    public function testWarningMessageWhenDelgatorSupporterAddDirectSupport()
    {
        print sprintf(" \n Warning appear when delegate support exists in topic and now adding direct support %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);

        $this->actingAs($user)->get('/api/v3/support/check?topic_num=416&camp_num=3');

        $this->seeJson([
            "warning"=> "You have delegated your support to user Brent_Allsop in this camp. If you continue your delegated support will be removed."
        ]);
    }  

  
    
}
