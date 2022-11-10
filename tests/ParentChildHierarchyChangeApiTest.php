<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class ParentChildHierarchyChangeApiTest extends TestCase
{

    use DatabaseTransactions;
    
    /**
     * Sunil Talentelgia
     * Camp hierarchy change test scenarios ---->
     * User can create any number of child camps at any level
     * User can support either parent camp or child camp(including grandparent/grandchild) at a time
     * Do not show the child camps in the parent camp dropdown list while updating the camp
     * While changing parent camp , only common support should get removed from parent camp(if any) and if any support is not common then it should remain the same for that camp.
     * The only support records that should be removed are stacked (individual supports parent of a child) parent records, leaving only the child support record in the stack.
    */
    
    /*
     * Login user change parent child hierarchy
    */
    public function testUnauthorizedUserCannotUpdate(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/manage-camp', []);
        $this->assertEquals(401, $response->status());
    }


    /*
     * If parent and child have common support then common support removed  only from parent camp and remain in child camp.
    */
    public function testCommonSupportRemovedFromParent(){
        print sprintf("\n This test verify common support is removed from parent and remain in child on parent child update %d %s", 200,PHP_EOL);
        $user = User::factory()->make([
            'id' => '413',
        ]);
        $data = [
            "topic_num" => 534,
            "camp_num"=>7,
            "nick_name"=>357, //RC
            "submitter"=>357, //RC
            "event_type"=>"update",
            "camp_id"=>3377,
            "camp_name"=>"camp 5",
            "parent_camp_num"=>4,  // This is new parent id
            "old_parent_camp_num"=>1 // This is previous parent id
        ];

        $this->actingAs($user)->post('/api/v3/manage-camp', $data);
        $this->assertEquals(200, $this->response->status());
        $this->actingAs($user)->get('/api/v3/support/check?topic_num=534&camp_num=4');
        $this->seeJson([
            "support_flag"=> 0
        ]);

    }
    /*
        *This test verify if common support is not exist in parent-child then Parent support remains same  
    */
    public function testSupportRemainSameIfNotCommon(){
        print sprintf("\n This test verify if common support is not exist in parent-child then Parent support remains same  %d %s", 200,PHP_EOL);
        $user = User::factory()->make([
            'id' => '413',
        ]);
        $data = [
            "topic_num" => 534,
            "camp_num"=>7,
            "nick_name"=>357,
            "submitter"=>357,
            "event_type"=>"update",
            "camp_id"=>3377,
            "camp_name"=>"camp 5",
            "parent_camp_num"=>5,  // This is new parent id
            "old_parent_camp_num"=>1 // This is previous parent id
        ];

        $this->actingAs($user)->post('/api/v3/manage-camp', $data);
        $this->assertEquals(200, $this->response->status());
        $this->actingAs($user)->get('/api/v3/support/check?topic_num=534&camp_num=5');
        $this->seeJson([
            "support_flag"=> 0
        ]);
    }

    /**
     * Check Api with empty form data
     * validation
    */
    public function testParentChildHierarchyEmptyData()
    {
        print sprintf("\n Test with empty form data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/manage-camp', []);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testParentChildHierarchyWithInvalidData()
    {
        $invalidData = [
            "topic_num" => 'c',
            "camp_num"=>3,
            "nick_name"=>457,
            "submitter"=>457,
            "event_type"=>"update",
            "camp_id"=>4303,
            "camp_name"=>"c2",
           "parent_camp_num"=>2,  // This is new parent id
            "old_parent_camp_num"=>1 // This is previous parent id
        ];
        print sprintf("\n Test with invalid values");
        $user = User::factory()->make([
            'id' => '1132',
        ]);
        $this->actingAs($user)->post('/api/v3/manage-camp', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

  
}
