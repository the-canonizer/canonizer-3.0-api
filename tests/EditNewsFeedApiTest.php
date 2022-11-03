<?php

use App\Models\User;

class EditNewsFeedApiTest extends TestCase
{
    
    /**
     * Check Api with empty form data
     * validation
     */
    public function testEditNewsFeedApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make([
            'type' => 'admin',
        ]);
        $this->actingAs($user)->post('/api/v3/edit-camp-newsfeed', []);
        $this->assertEquals(400,  $this->response->status());
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
        $user = User::factory()->make([
            'type' => 'admin',
        ]);
        $this->actingAs($user)->post('/api/v3/edit-camp-newsfeed', $emptyData);
        $this->assertEquals(400, $this->response->status());
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
        $user = User::factory()->make([
            'type' => 'admin',
        ]);
        $this->actingAs($user)->post('/api/v3/edit-camp-newsfeed', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testEditNewsFeedWithoutUserAuth()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make([
            'type' => 'admin',
        ]);
        $this->post('/api/v3/edit-camp-newsfeed', []);
        $this->assertEquals(401,  $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testEditNewsFeedApiResponse()
    {
        $data = [
            "newsfeed_id" => 1,
        ]; 
        print sprintf("\n Test News Feed API Response ", 200, PHP_EOL);
        $user = User::factory()->make([
            'type' => 'admin',
        ]);
        $this->actingAs($user)->post('/api/v3/edit-camp-newsfeed', $data);
        $this->seeJsonStructure([
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
