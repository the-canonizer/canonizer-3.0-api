<?php

namespace Tests;

use App\Models\User;
use App\Models\Topic;
use App\Models\Camp;
use App\Models\Nickname;
use App\Models\FeatureTopic;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetFeaturedTopicApiTest extends TestCase
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
        $this->camp = Camp::factory()->create([
            'topic_num' => $this->topic->topic_num,
            'camp_num' => 1,
            'submitter_nick_id' => $this->nickname->id
        ]);
        
        // Create a Featured Topic entry
        FeatureTopic::create([
            'topic_num' => $this->topic->topic_num,
            'camp_num' => $this->camp->camp_num,
            'active' => '1'
        ]);
    }

    public function testGetFeaturedTopicApi()
    {
        print sprintf("Call the get featured Topic Api");
        $response = $this->actingAs($this->user)->getJson('/api/v3/featured-topic');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'items',
                'current_page',
                'per_page',
                'last_page',
                'total_rows'
            ]
        ]);
    }

    public function testGetFeaturedTopicApiWithInvalidMethod()
    {
        print sprintf("Call the get featured Topic Api with invalid method");
        $response = $this->actingAs($this->user)->postJson('/api/v3/featured-topic');
        $response->assertStatus(405);
    }

    public function testGetFeaturedTopicApiWithInvalidURL()
    {
        print sprintf("Call the get featured Topic Api with invalid URL");
        $response = $this->actingAs($this->user)->getJson('/api/v3/featured-invalid');
        $response->assertStatus(404);
    }
}
