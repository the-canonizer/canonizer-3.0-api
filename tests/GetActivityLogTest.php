<?php

use App\Models\User;

class GetActivityLogTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetActivityLogApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-activity-log', []);
        $this->assertEquals(400,  $this->response->status());
    }


    /**
     * Check Api with empty data
     * validation
     */
    public function testGetActivityLogApiWithEmptyValues()
    {
        $emptyData = [
            "per_page" => "",
            "page" => "",
            "log_type" => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-activity-log', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testGetActivityLogApiWithInvalidData()
    {
        $invalidData = [
            "per_page" => "20",
            "page" => "1",
            "log_type" => "invalid"
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-activity-log', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testGetActivityLogApiWithValidData()
    {
        $validData = [
            "per_page" => "20",
            "page" => "1",
            "log_type" => "threads"
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-activity-log', $validData);
        $this->assertEquals(200,  $this->response->status());
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testGetActivityLogApiWithoutUserAuth()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->post('/api/v3/get-activity-log', []);
        $this->assertEquals(401,  $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetActivityLogApiResponse()
    {
        $data = [
            "per_page" => "20",
            "page" => "1",
            "log_type" => "threads"
        ];
        print sprintf("\n Test Get Activity Log API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-activity-log',$data);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                    'items',
                    'current_page',
                    'per_page',
                    'last_page',
                    'total_rows',
                    'from',
                    'to'
            ]
        ]);
    }
}
