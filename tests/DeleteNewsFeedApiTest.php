<?php

namespace Tests;

use App\Models\User;
use App\Models\NewsFeed;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DeleteNewsFeedApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Check Api with empty form data
     * validation
     */
    public function testDeleteNewsFeedApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->create([
            'type' => 'admin'
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/delete-camp-newsfeed', []);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testDeleteNewsFeedApiWithEmptyValues()
    {
        $emptyData = [
            'newsfeed_id' => ''
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->create([
            'type' => 'admin'
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/delete-camp-newsfeed', $emptyData);
        $response->assertStatus(400);
    }

    public function testDeleteNewsFeedApiWithFalseData()
    {
        $emptyData = [
            'newsfeed_id' => '0'
        ];
        print sprintf("Test with id that dose not exist");
        $user = User::factory()->create([
            'type' => 'admin'
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/delete-camp-newsfeed', $emptyData);
        $response->assertStatus(400);
    }

    /**
     * Check Api response code with correct data
     */

    public function testDeleteNewsFeedApiStatus()
    {
        $user = User::factory()->create(['type' => 'admin']);
        $newsFeed = NewsFeed::factory()->create(['author_id' => $user->id]);
        $data = ['newsfeed_id' => $newsFeed->id];
        print sprintf("\n post NewsFeed ", 200, PHP_EOL);
        $response = $this->actingAs($user)->postJson('/api/v3/delete-camp-newsfeed', $data);
        $response->assertStatus(200);
    }

    /**
     * Check Api without user Auth
     */
    public function testDeleteNewsFeedwithoutUserAuth()
    {
        $data = ['newsfeed_id' => 123];
        print sprintf("\n post NewsFeed ", 401, PHP_EOL);
        $response = $this->postJson(
            '/api/v3/delete-camp-newsfeed',
            $data
        );
        $response->assertStatus(401);
    }
}
