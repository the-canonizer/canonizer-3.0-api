<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddSupportApiTest extends TestCase
{

    use DatabaseTransactions, AddSupportApiTestSeedHelper;
    
    /***
     *  #userId used  362
     *  #ncikNameId used 347
     *  rupali.chavan9860@gmail.com
     */

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    public function testAddSupportWithValidData()
    {
        print sprintf(" \n  Add Direct Support %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);
        $data = [
            "topic_num" => 190,
            "add_camp" => 
                    [
                        "camp_num" => 1,
                        "support_order" => 1
                    ],
            "remove_camps" => [],
            "type" => "direct",
            "action" => "add",
            "nick_name_id" => 347,
            "order_update" => []
        ];

        $response = $this->actingAs($user)->post('/api/v3/support/add', $data);
        $response->assertStatus(200);
    }

    public function testAddSupportMessageWhenSupportDoNotExists()
    {
        print sprintf(" \n check if support exists Api... %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make();
        $response = $this->actingAs($user)->get('/api/v3/support/check?topic_num=715&camp_num=1');
        $response->assertStatus(200);
        $response->assertJsonPath('status_code', 200);
        $response->assertJsonPath('message', "This camp doesn't have your support");
        $response->assertJsonPath('data.support_flag', 0);
    }

    public function testWarningMessageIFSupportExistsButNoWarningMessage()
    {
        print sprintf(" \n Support Exists in topic and adding support in siblings and managing support should return no warning message.  %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);
        
        $response = $this->actingAs($user)->get('/api/v3/support/check?topic_num=173&camp_num=3');
        
        $response->assertStatus(200);
        $response->assertJsonPath('status_code', 200);
        $response->assertJsonPath('message', "This camp is already supported");
        $response->assertJsonPath('data.support_flag', 1);
        $response->assertJsonPath('data.camp_num', '3');
    }

    public function testWarningMessageIfSupportSwitchFromChildToParent()
    {
        print sprintf(" \n Warning Message appears when support exists in child and now switching to parent  %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);

        $response = $this->actingAs($user)->get('/api/v3/support/check?topic_num=173&camp_num=1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.warning', "\"Agreement\" is a parent camp to this list of child camps. If you commit support to \"Agreement\", the support of the camps in this list will be removed.");
    }

    public function testWarningMessageIfSupportSwitchedFromParentToChild()
    {
        print sprintf(" \n Warning appear when support exists in parent and now switching to child  %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);
        
        $response = $this->actingAs($user)->get('/api/v3/support/check?topic_num=735&camp_num=2');

        $response->assertStatus(200);
        $response->assertJsonPath('data.warning', "\"Camp 1\" is a child camp to \"Agreement\", so if you commit support to \"Camp 1\", the explicit support of the parent camp \"Agreement\" will be removed.");
    }

    public function testWarningMessageWhenDelgatorSupporterAddDirectSupport()
    {
        print sprintf(" \n Warning appear when delegate support exists in topic and now adding direct support %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make([
            'id' => '362',
        ]);

        $response = $this->actingAs($user)->get('/api/v3/support/check?topic_num=416&camp_num=3');

        $response->assertStatus(200);
        $response->assertJsonPath('data.warning', "You have delegated your support to user RC in this camp. If you continue your delegated support will be removed.");
    }  

}
