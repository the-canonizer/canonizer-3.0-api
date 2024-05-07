<?php

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
        $this->post('/api/v3/edit-topic');
        $this->assertEquals(401,  $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testEdiTopicRecordApiWithInvalidData()
    {
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/edit-topic',[]);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testEdiTopicRecordApiResponse()
    {
        print sprintf("\n Test edit topic API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/edit-topic');
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }

}
