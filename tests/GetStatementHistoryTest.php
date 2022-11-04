<?php

use App\Models\User;

class GetStatementHistoryTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetStatementHistoryApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-statement-history', []);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testGetStatementHistoryApiWithEmptyValues()
    {
        $emptyData = [
            "per_page" => "",
            "page" => "",
            "topic_num" => "",
            "camp_num" => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-statement-history', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testGetStatementHistoryApiWithValidData()
    {
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "camp_num" => "1"
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-statement-history', $validData);
        $this->assertEquals(200,  $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testGetStatementHistoryApiWithInvalidData()
    {
        $invalidData = [
            "per_page" => "10",
            "page" => "1",
            "type" => "invalid",
            "topic_num" => "88",
            "camp_num" => "1",
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-statement-history', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testGetStatementHistoryApiWithoutUserAuth()
    {
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "camp_num" => "1"
        ];
        $this->post('api/v3/get-statement-history', $validData);
        $this->assertEquals(200,  $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetStatementHistoryApiResponse() 
    {
        $data = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "type" => "all",
            "camp_num" => "1"
        ];
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-statement-history', $data);
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
                'to',
                'details'
            ]
        ]);
    }
}
