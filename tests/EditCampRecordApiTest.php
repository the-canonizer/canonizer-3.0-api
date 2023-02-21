<?php

use App\Models\User;

class EditCampRecordApiTest extends TestCase
{
  /**
     * Check Api without auth
     * validation
     */
    public function testEditCampApiWithoutUserAuth()
    {
        print sprintf("Test without auth");
       
        $this->post('/api/v3/edit-camp');
        //   dd($this->response);
        $this->assertEquals(401,  $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testEditCampApiWithInvalidData()
    {
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/edit-camp');
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testEditCampApiResponse()
    {
        print sprintf("\n Test edit Camp API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/edit-camp');
        // $this->assertEquals(200,  $this->response->status());
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }
}
