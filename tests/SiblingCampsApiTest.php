<?php

namespace Tests;

use App\Models\User;
use App\Models\Topic;
use App\Models\Camp;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SiblingCampsApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Check Api with empty form data
     * validation
     */
    public function testTopicTagListApiWithEmptyFormData() {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-sibling-camps', []);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty form values
     * validation
     */
    public function testGetSiblingCampsApiWithEmptyValues() {
        $emptyData = [
            "parent_camp_num" => "",
            "topic_num" => "",
            "camp_num" => "",
        ];
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-sibling-camps', $emptyData);
        $response->assertStatus(400);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testGetSiblingCampsApiWithInvalidData() {
        $invalidData = [
            "parent_camp_num" => "dd",
            "topic_num" => "df",
            "camp_num" => "ds",
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/get-sibling-camps', $invalidData);
        $response->assertStatus(400);
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testGetSiblingCampsApiWithValidData() {
        print sprintf("Test with valid values");
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $camp1 = Camp::factory()->create(['topic_num' => $topic->topic_num, 'parent_camp_num' => 1]);
        $camp2 = Camp::factory()->create(['topic_num' => $topic->topic_num, 'parent_camp_num' => 1]);

        $validData = [
            "parent_camp_num" => 1,
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp1->camp_num,
        ];
        
        $response = $this->actingAs($user)->postJson('/api/v3/get-sibling-camps', $validData);
        $response->assertStatus(200);
    }

    /**
     * Check Api response code with valid data
     * validation
     */
    public function testGetSiblingCampsApiResponseWithValidData() {
        print sprintf("Test with valid values");
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $camp1 = Camp::factory()->create(['topic_num' => $topic->topic_num, 'parent_camp_num' => 1]);
        $camp2 = Camp::factory()->create(['topic_num' => $topic->topic_num, 'parent_camp_num' => 1]);

        $validData = [
            "parent_camp_num" => 1,
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp1->camp_num,
        ];
        
        $response = $this->actingAs($user)->postJson('/api/v3/get-sibling-camps', $validData);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                '*' => [
                    'topic_num',
                    'camp_num',
                    'camp_name',
                    'submit_time',
                    'go_live_time',
                ]
            ]
        ]);
    }
}
