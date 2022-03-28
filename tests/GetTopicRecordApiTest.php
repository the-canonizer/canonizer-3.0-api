<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class GetTopicRecordApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetTopicRecordApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v3/get-topic-record', []);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetTopicRecordApiWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => ''
        ];
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v3/get-topic-record', $emptyData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api response code with correct data
     */
    public function testGetTopicRecordApiStatus()
    {
        $data = [
            'topic_num' => 45,
            'camp_num' => 1
        ];
        print sprintf("\n get topic record ", 200, PHP_EOL);
        $response = $this->call(
            'post',
            '/api/v3/get-topic-record',
            $data
        );
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetTopicRecordApiResponse()
    {
        $data = [
            'topic_num' => 45,
            'camp_num' => 1
        ];

        print sprintf("\n Test GetTopicRecord API Response ", 200, PHP_EOL);
        $this->call('POST', '/api/v3/get-topic-record', $data);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'topic_num',
                    'camp_num',
                    'topic_name',
                    'namespace_name'
                ]
            ]
        ]);
    }
}
