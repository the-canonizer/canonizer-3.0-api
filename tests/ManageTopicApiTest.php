<?php

namespace Tests;

use App\Models\User;
use App\Models\Support;
use App\Models\Topic;
use App\Models\Nickname;
use App\Models\Namespaces;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ManageTopicApiTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $nickname;
    protected $topic;
    protected $namespace;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->nickname = Nickname::factory()->create(['user_id' => $this->user->id]);
        
        // Ensure at least one namespace exists
        $this->namespace = Namespaces::first() ?? Namespaces::factory()->create();
        
        $this->topic = Topic::factory()->create([
            'submitter_nick_id' => $this->nickname->id,
            'namespace_id' => $this->namespace->id
        ]);
    }

     /**
     * Check Api with empty form data
     * validation
     */
    public function testManageTopicApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-topic', []);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testManageTopicApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "topic_id" => "",
            "nick_name" => "",
            "topic_name" => "",
            "submitter" => "",
            "namespace_id" => "",
            "note" => "",
            "event_type" => "",
        ];
        print sprintf("Test with empty values");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-topic', $emptyData);
        $response->assertStatus(400);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testManageTopicApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => $this->topic->topic_num,
            "topic_id" => $this->topic->id,
            "nick_name" => $this->nickname->id,
            "topic_name" => "1",
            "submitter" => $this->nickname->id,
            "namespace_id" => $this->namespace->id,
            "note" => "1",
            "event_type" => "533",
        ];
        print sprintf("Test with invalid values");
        
        Support::factory()->create([
            'nick_name_id' => $this->nickname->id,
            'topic_num' => $this->topic->topic_num,
            'camp_num'  =>  1,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-topic', $invalidData);
        $response->assertStatus(400);
    }


     /**
     * Check Api with valid data
     * validation
     */
    public function testUpdateManageTopicWithValidData()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "topic_id" => $this->topic->id,
            "nick_name" => $this->nickname->id,
            "topic_name" => rand(),
            "submitter" => $this->nickname->id,
            "namespace_id" => $this->namespace->id,
            "note" => "1",
            "event_type" => "update",
        ];
        print sprintf("Test with valid values for updating topic based on a version");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-topic', $validData);
        $response->assertStatus(200);
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testObjectionManageTopicWithValidDataAfterChangeIsSubmitted()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "topic_id" => $this->topic->id,
            "nick_name" => $this->nickname->id,
            "topic_name" => rand(),
            "submitter" => $this->nickname->id,
            "namespace_id" => $this->namespace->id,
            "note" => "1",
            "event_type" => "objection",
            "objection_reason" => "reason",
        ];
        print sprintf("Test with valid values for objecting a topic after the change is submitted");
        
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-topic', $validData);
        $response->assertStatus(400); // Should fail if not a supporter or if direct supporter
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testEditManageTopicWithValidData()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "topic_id" => $this->topic->id,
            "nick_name" => $this->nickname->id,
            "topic_name" =>  rand(),
            "submitter" => $this->nickname->id,
            "namespace_id" => $this->namespace->id,
            "note" => "1",
            "event_type" => "edit"
        ];
        print sprintf("Test with valid values for editing a topic");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-topic', $validData);
        $response->assertStatus(200);
    }


    /**
     * Check Api without auth
     * validation
     */
    public function testManageTopicApiWithoutAuth()
    {
        $response = $this->postJson('/api/v3/manage-topic', []);
        $response->assertStatus(401);
    }

    public function testUpdateManageTopicWithValidDataToCheckGracePeriod()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "topic_id" => $this->topic->id,
            "nick_name" => $this->nickname->id,
            "topic_name" => rand(),
            "submitter" => $this->nickname->id,
            "namespace_id" => $this->namespace->id,
            "note" => "1",
            "event_type" => "update",
        ];
        print sprintf("Test with valid values for updating topic and check if it is in grace period");
        $response = $this->actingAs($this->user)->postJson('/api/v3/manage-topic', $validData);
        $response->assertStatus(200);

        $topic = Topic::where('submitter_nick_id', $this->nickname->id)->orderBy('id', 'desc')->first();
        $this->assertNotNull($topic);
        $this->assertEquals(1, $topic->grace_period);
    }
}
