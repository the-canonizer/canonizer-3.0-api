<?php

namespace Tests;

use App\Models\Topic;
use App\Models\Camp;
use App\Models\Nickname;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;


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
        $response = $this->postJson('/api/v3/manage-camp', []);
        $response->assertStatus(401);
    }


    /*
     * If parent and child have common support then common support removed  only from parent camp and remain in child camp.
    */
    public function testCommonSupportRemovedFromParent(){
        print sprintf("\n This test verify common support is removed from parent and remain in child on parent child update %d %s", 200,PHP_EOL);
        $user = User::factory()->create();
        $nickname = Nickname::factory()->create(['user_id' => $user->id]);
        $topic = Topic::factory()->create(['submitter_nick_id' => $nickname->id]);
        $parentCamp = Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'parent_camp_num' => 1,
            'submitter_nick_id' => $nickname->id
        ]);
        $newParentCamp = Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'parent_camp_num' => 1,
            'submitter_nick_id' => $nickname->id
        ]);
        $camp = Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'parent_camp_num' => $parentCamp->camp_num,
            'submitter_nick_id' => $nickname->id
        ]);

        $data = [
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num,
            "nick_name" => $nickname->id,
            "submitter" => $nickname->id,
            "event_type" => "update",
            "camp_id" => $camp->id,
            "camp_name" => $camp->camp_name . " Updated",
            "parent_camp_num" => $newParentCamp->camp_num,
            "old_parent_camp_num" => $parentCamp->camp_num,
            "from_test_case" => 1
        ];

        $response = $this->actingAs($user)->postJson('/api/v3/manage-camp', $data);
        $response->assertStatus(200);
    }
    /*
        *This test verify if common support is not exist in parent-child then Parent support remains same  
    */
    public function testSupportRemainSameIfNotCommon(){
        print sprintf("\n This test verify if common support is not exist in parent-child then Parent support remains same  %d %s", 200,PHP_EOL);
        $user = User::factory()->create();
        $nickname = Nickname::factory()->create(['user_id' => $user->id]);
        $topic = Topic::factory()->create(['submitter_nick_id' => $nickname->id]);
        $parentCamp = Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'parent_camp_num' => 1,
            'submitter_nick_id' => $nickname->id
        ]);
        $newParentCamp = Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'parent_camp_num' => 1,
            'submitter_nick_id' => $nickname->id
        ]);
        $camp = Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'parent_camp_num' => $parentCamp->camp_num,
            'submitter_nick_id' => $nickname->id
        ]);

        $data = [
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num,
            "nick_name" => $nickname->id,
            "submitter" => $nickname->id,
            "event_type" => "update",
            "camp_id" => $camp->id,
            "camp_name" => $camp->camp_name . " Updated",
            "parent_camp_num" => $newParentCamp->camp_num,
            "old_parent_camp_num" => $parentCamp->camp_num,
            "from_test_case" => 1
        ];

        $response = $this->actingAs($user)->postJson('/api/v3/manage-camp', $data);
        $response->assertStatus(200);
    }

    /**
     * Check Api with empty form data
     * validation
    */
    public function testParentChildHierarchyEmptyData()
    {
        print sprintf("\n Test with empty form data");
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/manage-camp', []);
        $response->assertStatus(400);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testParentChildHierarchyWithInvalidData()
    {
        $user = User::factory()->create();
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
        $response = $this->actingAs($user)->postJson('/api/v3/manage-camp', $invalidData);
        $response->assertStatus(400);
    }

  
}
