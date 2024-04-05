<?php

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
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/commit/change', []);
        $this->assertEquals(400, $this->response->status());
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
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/commit/change', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    public function testCommitChangeAndNotifyApiWithFalseData()
    {
        $invalidData = [
            'id' => 1,
            'type' => 'wrong'
        ];
        print sprintf("Test with invalid data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/commit/change', $invalidData);
        $this->assertEquals(400, $this->response->status());
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
        $this->post(
            '/api/v3/commit/change',
            $data
        );
        $this->assertEquals(401, $this->response->status());
    }
}
