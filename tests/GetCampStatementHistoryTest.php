<?php

use App\Models\User;

class GetCampStatementHistoryTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampStatementHistoryApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-statement-history', []);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetCampStatementHistoryApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "camp_num" => "",
            "type" => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-statement-history', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with False values
     * validation
     */
    public function testGetCampStatementHistoryApiWithFalseData()
    {
        $emptyData = [
            "topic_num" => "abc",
            "camp_num" => "abc",
            "type" => "xyz"
        ];
        print sprintf("Test with id that dose not exist");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-statement-history', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api response code with correct data
     */
    public function testGetCampStatementHistoryApiStatus()   
    {
        $data = [
            "topic_num" => "1",
            "camp_num" => "1",
            "type" => "all",
            "per_page" => 10,
            "page" => 1
        ];
        print sprintf("\n with correct form data ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post(
            '/api/v3/get-statement-history',
            $data
        );
        $this->assertEquals(200, $this->response->status());
    }

     /**
     * Check Api response structure
     */
    public function testGetCampStatementHistoryApiResponse()  
    {
        $data = [
            "topic_num" => "320",
            "camp_num" => "1",
            "type" => "all",
            "per_page" => 10,
            "page" => 1
        ];
        print sprintf("\n Test API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-statement-history', $data);
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

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetCampStatementHistoryApiWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => 45,
            'camp_num' => 1,
            "type" => "all",
            'as_of' => "bydate"
        ];

        print sprintf("Test with invalid values");
        $response = $this->call('POST', '/api/v3/get-statement-history', $invalidData);
        $this->assertEquals(400, $response->status());
    }

}
