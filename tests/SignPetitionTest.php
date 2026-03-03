<?php

namespace Tests;

use App\Models\User;
use App\Models\Nickname;
use App\Models\Topic;
use App\Models\Camp;
use App\Models\Support;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SignPetitionTest extends TestCase
{
    use DatabaseTransactions;
    
    public function testSignPetitionWithEmptyFormData()
    {
        print sprintf("\n Test with empty form data\n");
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/camp/sign', []);
        $response->assertStatus(400);
    }   
    
    public function testSignPetitionApiWithEmptyValues()
    {
        $payload = [
            "nick_name_id" => 0,
            "topic_num" => 0,
            "camp_num" => 0
        ];
        print sprintf("\nTest with empty values\n");
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/camp/sign', $payload);
        $response->assertStatus(400);
    }

    public function testSignPetitionApiWithWrongNickname()
    {
        print sprintf("Test with wrong nickname");
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherNickname = Nickname::factory()->create(['user_id' => $otherUser->id]);
        $topic = Topic::factory()->create();
        $camp = Camp::factory()->create(['topic_num' => $topic->topic_num]);

        $payload = [
            "nick_name_id" => $otherNickname->id,
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num
        ];

        $response = $this->actingAs($user)->postJson('/api/v3/camp/sign', $payload);
        // Should return 403 because nick_name_id doesn't belong to the authenticated user
        $response->assertStatus(403);
    }

    public function testSignPetitionApiWithMissingKey()
    {
        print sprintf("Test with missing key");
        $user = User::factory()->create();
        $nickname = Nickname::factory()->create(['user_id' => $user->id]);
        $topic = Topic::factory()->create();
        $camp = Camp::factory()->create(['topic_num' => $topic->topic_num]);

        // Missing topic_num
        $payload = [
            "nick_name_id" => $nickname->id,
            "camp_num" => $camp->camp_num
        ];
        $response = $this->actingAs($user)->postJson('/api/v3/camp/sign', $payload);
        $response->assertStatus(400);

        // Missing camp_num
        $payload = [
            "nick_name_id" => $nickname->id,
            "topic_num" => $topic->topic_num
        ];
        $response = $this->actingAs($user)->postJson('/api/v3/camp/sign', $payload);
        $response->assertStatus(400);

        // Missing nick_name_id
        $payload = [
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num
        ];
        $response = $this->actingAs($user)->postJson('/api/v3/camp/sign', $payload);
        $response->assertStatus(400);
    }

    public function testSignPetitionApiWithValidData()
    {
        print sprintf("Test with valid values");
        $user = User::factory()->create();
        $nickname = Nickname::factory()->create(['user_id' => $user->id]);
        
        $otherUser = User::factory()->create();
        $otherNickname = Nickname::factory()->create(['user_id' => $otherUser->id]);
        
        $topic = Topic::factory()->create();

        // Create Agreement camp (required by SendEmailToSubscribersAndSupporters)
        Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'camp_num' => 1,
            'camp_name' => 'Agreement',
            'parent_camp_num' => null,
        ]);

        $camp = Camp::factory()->create([
            'topic_num' => $topic->topic_num, 
            'parent_camp_num' => 1,
            'camp_num' => 2
        ]);
        
        // Add a direct supporter to satisfy TopicSupport::signPetition logic (Case 2)
        Support::factory()->create([
            'topic_num' => $topic->topic_num,
            'camp_num' => $camp->camp_num,
            'nick_name_id' => $otherNickname->id,
            'support_order' => 1,
            'start' => time() - 100 // Ensure it's in the past
        ]);

        $payload = [
            "nick_name_id" => $nickname->id,
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num
        ];

        $response = $this->actingAs($user)->postJson('/api/v3/camp/sign', $payload);

        $response->assertStatus(200);
    }

}
