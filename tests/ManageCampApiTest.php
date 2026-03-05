<?php

namespace Tests;

use App\Models\Camp;
use App\Models\User;
use App\Models\Support;
use App\Models\Topic;
use App\Models\Nickname;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ManageCampApiTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $nickname;
    protected $topic;
    protected $camp;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->nickname = Nickname::factory()->create(['user_id' => $this->user->id]);
        $this->topic = Topic::factory()->create(['submitter_nick_id' => $this->nickname->id]);
        
        // Agreement camp is mandatory for all topics
        Camp::factory()->create([
            'topic_num' => $this->topic->topic_num,
            'submitter_nick_id' => $this->nickname->id,
            'camp_num' => 1,
            'camp_name' => 'Agreement'
        ]);

        // Create a secondary camp for testing updates/edits
        $this->camp = Camp::factory()->create([
            'topic_num' => $this->topic->topic_num,
            'submitter_nick_id' => $this->nickname->id,
            'camp_num' => 2,
            'camp_name' => 'Camp 2'
        ]);
    }
     /**
     * Check Api with empty form data
     * validation
     */
    public function testManageCampApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-camp', []);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testManageCampApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "camp_num" => "",
            "nick_name" => "",
            "note" => "",
            "submitter" => "",
            "camp_id" => $this->camp->id,
            "camp_name" => rand(),
            "camp_about_nick_id" => "",
        ];
        print sprintf("Test with empty values");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-camp', $emptyData);
        $response->assertStatus(400);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testManageCampApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "nick_name" => $this->nickname->id,
            "note" => "note",
            "submitter" => $this->nickname->id,
            "objection" => "1",
            "event_type" => "objection",
        ];
        print sprintf("Test with invalid values");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-camp', $invalidData);
        $response->assertStatus(400);
    }


     /**
     * Check Api with valid data
     * validation
     */
    public function testUpdateManageCampWithValidData()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "camp_id" => $this->camp->id,
            "camp_name" => rand(),
            "nick_name" => $this->nickname->id,
            "camp_about_nick_id" => $this->nickname->id,
            "note" => "note",
            "submitter" => $this->nickname->id,
            "event_type" => "update",
        ];
        print sprintf("Test with valid values for updating camp based on a version");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-camp', $validData);
        $response->assertStatus(200);
    }

    public function testUpdateManageCampWithInvalidCampLeader()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "camp_id" => $this->camp->id,
            "camp_name" => rand(),
            "nick_name" => $this->nickname->id,
            "camp_about_nick_id" => $this->nickname->id,
            "note" => "note",
            "submitter" => $this->nickname->id,
            "event_type" => "update",
            "camp_leader_nick_id" => "393939399339"
        ];
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-camp', $validData);
        $response->assertStatus(400);
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testObjectionManageCampWithValidDataAfterChangeIsSubmitted()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "camp_id" => $this->camp->id,
            "camp_name" => rand(),
            "nick_name" => $this->nickname->id,
            "note" => "note",
            "camp_about_nick_id" => $this->nickname->id,
            "submitter" => $this->nickname->id,
            "event_type" => "objection",
            "objection_reason" => "reason",
        ];
        print sprintf("Test with valid values for objecting a camp after the change is submitted");
        
        Support::factory()->create([
            'nick_name_id' => $this->nickname->id,
            'topic_num' => $this->topic->topic_num,
            'camp_num'  =>  $this->camp->camp_num,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-camp', $validData);
        $response->assertStatus(400); // Direct supporter can object their own change
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testEditManageCampWithValidData()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "nick_name" => $this->nickname->id,
            "camp_about_nick_id" => $this->nickname->id,
            "camp_name" => rand(),
            "camp_id" => $this->camp->id,
            "note" => "note",
            "submitter" => $this->nickname->id,
            "keywords" => "1",
            "event_type" => "edit",
        ];
        print sprintf("Test with valid values for editing a camp");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-camp', $validData);
        $response->assertStatus(200);
    }


    /**
     * Check Api without auth
     * validation
     */
    public function testManageCampApiWithoutAuth()
    {
        $response = $this->postJson('/api/v3/manage-camp', []);
        $response->assertStatus(401);
    }
    
    public function testUpdateManageCampWithValidDataToCheckGracePeriod()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "camp_id" => $this->camp->id,
            "camp_name" => rand(),
            "nick_name" => $this->nickname->id,
            "camp_about_nick_id" => $this->nickname->id,
            "note" => "note",
            "submitter" => $this->nickname->id,
            "event_type" => "update",
        ];
        print sprintf("Test with valid values for updating camp & check the change should be in grace period");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-camp', $validData);
        $response->assertStatus(200);
        
        $camp = Camp::where('submitter_nick_id', $this->nickname->id)->orderBy('id', 'desc')->first();
        $this->assertNotNull($camp);
        $this->assertEquals(1, $camp->grace_period);
    }
}