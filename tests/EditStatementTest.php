<?php

namespace Tests;

use App\Models\User;

class EditStatementTest extends TestCase
{
    /**
     * Check Api without auth
     * validation
     */
    public function testEditStatementApiWithoutUserAuth()
    {
        print sprintf("Test without auth");
        $response = $this->post('/api/v3/edit-camp-statement');
        $response->assertStatus(401);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testEditStatementApiWithInvalidData()
    {
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $response = $this->actingAs($user)->post('/api/v3/edit-camp-statement',[]);
        $response->assertStatus(400);
    }

    /**
     * Check Api response structure
     */
    public function testEditStatementApiResponse()  
    {
        print sprintf("\n Test edit statement API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $response = $this->actingAs($user)->post('/api/v3/edit-camp-statement/');
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }

}
