<?php

namespace Tests;

use App\Models\User;
use App\Models\Topic;
use App\Models\Camp;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetCampActivityLogApiTest extends TestCase
{
    use DatabaseTransactions;

    public function testWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $apiPayload = [];
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-activity-log', $apiPayload);
        $response->assertStatus(400);
    }

    public function testWithEmptyValues()
    {
        print sprintf("\nTest with empty values");
        $apiPayload = [
            'topic_num' => '',
            'camp_num' => ''
        ];
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-activity-log', $apiPayload);
        $response->assertStatus(400);
    }

    public function testWithInvaidTopicNum()
    {
        print sprintf("\nTest with invalid topic_num");
        $apiPayload = [
            'topic_num' => 12312312,
            'camp_num' => 1
        ];
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-activity-log', $apiPayload);
        // ActivityController returns 200 with error message if no activity is found
        $response->assertStatus(200);
    }

    public function testIfActivityIsNotLogged()
    {
        print sprintf("\nTest if activity is not logged");
        $topic = Topic::factory()->create();
        $camp = Camp::factory()->create(['topic_num' => $topic->topic_num]);

        $apiPayload = [
            'topic_num' => $topic->topic_num,
            'camp_num' => $camp->camp_num
        ];
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-activity-log', $apiPayload);
        // Should return 200 with "no activity logged" message
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => '']);
    }

    public function testWithValidValues()
    {
        print sprintf("\nTest with valid values");
        $topic = Topic::factory()->create();
        $camp = Camp::factory()->create(['topic_num' => $topic->topic_num]);
        
        $user = User::factory()->create();

        // Manually log an activity
        DB::table('activity_log')->insert([
            'log_name' => 'topic/camps',
            'description' => 'Test Activity',
            'properties' => json_encode([
                'topic_num' => $topic->topic_num,
                'camp_num' => $camp->camp_num,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $apiPayload = [
            'topic_num' => $topic->topic_num,
            'camp_num' => $camp->camp_num
        ];
        
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-activity-log', $apiPayload);
        $response->assertStatus(200);
    }

    public function testApiStructureValidValues()
    {
        print sprintf("\nTest api structure with valid values");
        $topic = Topic::factory()->create();
        $camp = Camp::factory()->create(['topic_num' => $topic->topic_num]);
        
        $user = User::factory()->create();

        // Manually log an activity
        DB::table('activity_log')->insert([
            'log_name' => 'topic/camps',
            'description' => 'Test Activity',
            'properties' => json_encode([
                'topic_num' => $topic->topic_num,
                'camp_num' => $camp->camp_num,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $apiPayload = [
            'topic_num' => $topic->topic_num,
            'camp_num' => $camp->camp_num
        ];
        
        $response = $this->actingAs($user)->postJson('/api/v3/get-camp-activity-log', $apiPayload);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                'items' => []
            ]
        ]);
    }
}
