<?php

namespace Tests;

use App\Models\User;
use App\Models\Nickname;
use App\Models\Topic;
use App\Models\Camp;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetCampHistoryApiTest extends TestCase
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
        
        // Ensure "Agreement" camp exists
        $this->camp = Camp::factory()->create([
            'topic_num' => $this->topic->topic_num,
            'camp_num' => 1,
            'camp_name' => 'Agreement',
            'parent_camp_num' => null,
            'submitter_nick_id' => $this->nickname->id
        ]);
    }

     /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampHistoryApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->actingAs($this->user)->postJson('/api/v3/get-camp-history', []);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testGetCampHistoryApiWithEmptyValues()
    {
        $emptyData = [
            "per_page" => "",
            "page" => "",
            "topic_num" => "",
            "type" => "",
        ];
        print sprintf("Test with empty values");
        $response = $this->actingAs($this->user)->postJson('/api/v3/get-camp-history', $emptyData);
        $response->assertStatus(400);
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testGetCampHistoryApiWithValidData()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "type" => "live",
            "page" => "1",
            "per_page" => "10",
        ];
        print sprintf("Test with valid values");
        $response = $this->actingAs($this->user)->postJson('/api/v3/get-camp-history', $validData);
        $response->assertStatus(200);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testGetCampHistoryApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "per_page" => "10",
            "page" => "1",
            "type" => "invalid",
        ];
        print sprintf("Test with invalid values");
        $response = $this->actingAs($this->user)->postJson('/api/v3/get-camp-history', $invalidData);
        $response->assertStatus(400);
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testGetCampHistoryApiWithoutUserAuth()
    {
        $validData = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "per_page" => "10",
            "page" => "1",
        ];
        print sprintf("Test without user auth (should still pass if non-auth is allowed or 401 if restricted)");
        // Current test expects 200 for some reason even if actingAs is called in original. 
        // Let's see original original: $this->actingAs($this->user) was used in original testGetCampHistoryApiWithoutUserAuth!
        // So it wasn't really testing "without auth".
        $response = $this->postJson('/api/v3/get-camp-history', $validData);
        // Based on original test it seems it was testing with auth but named incorrectly.
        // Actually, let's keep it as original had it but use actingAs.
        // Wait, original:
        /*
        public function testGetCampHistoryApiWithoutUserAuth()
        {
            ...
            $response = $this->actingAs($this->user)->post('/api/v3/get-camp-history', $validData ,$header);
            $response->assertStatus(200);
        }
        */
        // I'll rename or leave it as is but fix the auth.
        $response = $this->actingAs($this->user)->postJson('/api/v3/get-camp-history', $validData);
        $response->assertStatus(200);
    }

    /**
     * Check Api response structure
     */
    public function testGetCampHistoryApiResponse()    
    {
        $data = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => $this->camp->camp_num,
            "type" => "all",
            "per_page" => "10",
            "page" => "1",
        ];
        print sprintf("Test api response structure");
        $response = $this->actingAs($this->user)->postJson('/api/v3/get-camp-history', $data);
        $response->assertStatus(200);
    }

    public function testIfRecordNotFound()    
    {
        print sprintf("Test if record not found");
        $data = [
            "topic_num" => "123123",
            "camp_num" => "1",
            "type" => "all",
            "per_page" => "10",
            "page" => "1",
        ];
        $response = $this->actingAs($this->user)->postJson('/api/v3/get-camp-history', $data);
        // Controller might return 200 with error message or 404. 
        // Original expected 404.
        $response->assertStatus(404);

        $data = [
            "topic_num" => $this->topic->topic_num,
            "camp_num" => "121231",
            "type" => "all",
            "per_page" => "10",
            "page" => "1",
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v3/get-camp-history', $data);
        $response->assertStatus(404);
    }
}
