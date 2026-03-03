<?php

namespace Tests;

use App\Models\User;

class EditTopicRecordApiTest extends TestCase
{
    /**
     * Check Api without auth
     * validation
     */
    public function testEdiTopicRecordApiWithoutUserAuth()
    {
        print sprintf("Test without auth");
        $response = $this->post('/api/v3/edit-topic');
        $response->assertStatus(401);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testEdiTopicRecordApiWithInvalidData()
    {
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $response = $this->actingAs($user)->post('/api/v3/edit-topic',[]);
        $response->assertStatus(400);
    }

    /**
     * Check Api response structure
     */
    public function testEdiTopicRecordApiResponse()
    {
        print sprintf("\n Test edit topic API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $response = $this->actingAs($user)->post('/api/v3/edit-topic');
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }

}
