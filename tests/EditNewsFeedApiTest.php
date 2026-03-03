<?php

namespace Tests;

use App\Models\User;
use App\Models\NewsFeed;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EditNewsFeedApiTest extends TestCase
{
    use DatabaseTransactions;
    
    /**
     * Check Api with empty form data
     * validation
     */
    public function testEditNewsFeedApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->create([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/edit-camp-newsfeed', []);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testEditNewsFeedApiWithEmptyValues()
    {
        $emptyData = [
            "newsfeed_id" => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->create([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/edit-camp-newsfeed', $emptyData);
        $response->assertStatus(400);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testEditNewsFeedApiWithInvalidData()
    {
        $invalidData = [
            "newsfeed_id" => "abc",
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->create([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/edit-camp-newsfeed', $invalidData);
        $response->assertStatus(400);
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testEditNewsFeedWithoutUserAuth()
    {
        print sprintf("Test with empty form data");
        $response = $this->postJson('/api/v3/edit-camp-newsfeed', []);
        $response->assertStatus(401);
    }

    /**
     * Check Api response structure
     */
    public function testEditNewsFeedApiResponse()
    {
        print sprintf("\n Test News Feed API Response ", 200, PHP_EOL);
        $user = User::factory()->create([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);

        $newsfeed = NewsFeed::factory()->create([
            'author_id' => $user->id
        ]);

        $data = [
            "newsfeed_id" => $newsfeed->id,
        ];

        $response = $this->actingAs($user)->postJson('/api/v3/edit-camp-newsfeed', $data);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'id',
                    'display_text',
                    'link',
                    'available_for_child',
                    'submitter_nick_id'
                ]
            ]
        ]);
    }
}
