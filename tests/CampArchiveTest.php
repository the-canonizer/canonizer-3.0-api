<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Camp;

class CampArchiveTest extends TestCase
{
    use DatabaseTransactions;

    public function testArchiveCampApiWithoutUserAuth()
    {
        print sprintf("Test without auth  %d %s", 401,PHP_EOL);
        $response = $this->postJson('/api/v3/manage-camp', []);
        $response->assertStatus(401);
    }

    public function testArchiveCampApiWithInvalidData()
    {
        print sprintf("Test with invalid data  %d %s", 400, PHP_EOL);
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v3/manage-camp');
        $response->assertStatus(400);
    }

    public function testArchiveCampWithValiddata()
    {
        $user = User::factory()->create(['status' => 1]);
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create(['submitter_nick_id' => $nickname->id]);
        $camp = \App\Models\Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'parent_camp_num' => 1,
            'camp_num' => 2,
            'submitter_nick_id' => $nickname->id
        ]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num,
            "camp_id" => $camp->id,
            "nick_name" => $nickname->id, 
            "submitter" => $nickname->id, 
            "event_type" => "update",
            "camp_name" => "updated camp name",
            "parent_camp_num" => 1,
            "is_archive" => 1,
        ];

        print sprintf("Archive camp with valid values ");
        $response = $this->actingAs($user)->postJson('/api/v3/manage-camp', $validData);
        if ($response->status() !== 200) {
            print_r($response->json());
        }
        $response->assertStatus(200);
        
        $responseData = $response->getData();
        $this->assertEquals(1, $responseData->data->is_archive);
    }

}
