<?php

use App\Models\User;

class EditStatmentTest extends TestCase
{
    /**
     * Check Api without auth
     * validation
     */
    public function testEditStatementApiWithoutUserAuth()
    {
        print sprintf("Test without auth");
        $this->get('/api/v3/edit-camp-statement/1');
        $this->assertEquals(401,  $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testEditStatementApiWithInvalidData()
    {
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->get('/api/v3/edit-camp-statement/abc');
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testEditStatementApiResponse()  
    {
        print sprintf("\n Test edit statement API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->get('/api/v3/edit-camp-statement/1');
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                    'statement',
                    'topic',
                    'parent_camp',
                    'nick_name',
                    'parent_camp_num'
            ]
        ]);
    }

}
