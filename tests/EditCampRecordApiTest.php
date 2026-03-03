<?php

namespace Tests;

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
       
        $response = $this->post('/api/v3/edit-camp');
        //   dd($response);
        $response->assertStatus(401);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testEditCampApiWithInvalidData()
    {
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $response = $this->actingAs($user)->post('/api/v3/edit-camp');
        $response->assertStatus(400);
    }

    /**
     * Check Api response structure
     */
    public function testEditCampApiResponse()
    {
        print sprintf("\n Test edit Camp API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $response = $this->actingAs($user)->post('/api/v3/edit-camp');
        // $response->assertStatus(200);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }

        /**
     * Check Api response structure is valid 
     */
    public function testEditCampApiResponseIsValid()
    {
        $user = User::factory()->make();
        $editCampData = [
            "record_id" => "224",
            "event_type" => "edit"
        ];
        $response = $this->actingAs($user)->post('/api/v3/edit-camp', $editCampData);
        $response->assertStatus(200)->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                "camp",
                "nick_name",
                "eligible_camp_leaders",
                "topic",
                "parent_camp"
            ]
        ]);
    }
}
