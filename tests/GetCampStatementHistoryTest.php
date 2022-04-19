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
            "topic_num" => "320",
            "camp_num" => "1",
            "type" => "all"
        ];
        print sprintf("\n post NewsFeed ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post(
            '/api/v3/get-statement-history',
            $data
        );
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api without user Auth
     */
    public function testGetCampStatementHistorywithoutUserAuth()
    {
        $data = [
            'newsfeed_id' => 299
        ];
        print sprintf("\n post NewsFeed ", 200, PHP_EOL);
        $this->post(
            '/api/v3/get-statement-history',
            $data
        );
        $this->assertEquals(401, $this->response->status());
    }

     /**
     * Check Api response structure
     */
    public function testGetCampStatementHistoryApiResponse()
    {
        $data = [
            "topic_num" => "320",
            "camp_num" => "1",
            "type" => "all"
        ];
        print sprintf("\n Test News Feed API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/get-statement-history', $data);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'statement',
                    'topic',
                    'liveCamp',
                    'parentCamp',
                    'ifSupportDelayed',
                    'ifIamSupporter'
                ]
            ]
        ]);
    }
}
