<?php

use App\Models\User;

class GetTopicHistoryApiTest extends TestCase
{
     /**
     * Check Api with empty form data
     * validation
     */
    public function testGetTopicHistoryApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-topic-history', []);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testGetTopicHistoryApiWithEmptyValues()
    {
        $emptyData = [
            "per_page" => "",
            "page" => "",
            "topic_num" => "",
            "type" => "",
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-topic-history', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testGetTopicHistoryApiWithValidData()
    {
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "type" => "live",
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-topic-history', $validData);
        $this->assertEquals(200,  $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testGetTopicHistoryApiWithInvalidData()
    {
        $invalidData = [
            "per_page" => "10",
            "page" => "1",
            "type" => "invalid",
            "topic_num" => "88",
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-topic-history', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testGetTopicHistoryApiWithoutUserAuth()
    {
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
        ];
        $this->post('api/v3/get-topic-history', $validData);
        $this->assertEquals(200,  $this->response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetTopicHistoryApiResponse()
    {
        $data = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "88",
            "type" => "all",
        ];
        $user = User::factory()->make();
        $this->actingAs($user)->post('api/v3/get-topic-history', $data);
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
