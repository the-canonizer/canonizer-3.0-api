<?php

namespace Tests;

use App\Models\User;

class CommitChangeAndNotifyApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testCommitChangeAndNotifyAPIWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->create(['status' => 1]);
        $response = $this->actingAs($user)->post('/api/v3/commit/change', []);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testCommitChangeAndNotifyApiWithEmptyValues()
    {
        $emptyData = [
            'id' => '',
            'type' => ''
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->create(['status' => 1]);
        $response = $this->actingAs($user)->post('/api/v3/commit/change', $emptyData);
        $response->assertStatus(400);
    }

    public function testCommitChangeAndNotifyApiWithFalseData()
    {
        $invalidData = [
            'id' => 1,
            'type' => 'wrong'
        ];
        print sprintf("Test with invalid data");
        $user = User::factory()->create(['status' => 1]);
        $response = $this->actingAs($user)->post('/api/v3/commit/change', $invalidData);
        $response->assertStatus(400);
    }

    /**
     * Check Api without user Auth
     */
    public function testCommitChangeAndNotifywithoutUserAuth()
    {
        $data = [
            'id' => 1,
            'type' => 'wrong'
        ];
        $response = $this->post(
            '/api/v3/commit/change',
            $data
        );
        $response->assertStatus(401);
    }
}
