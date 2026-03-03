<?php

namespace Tests;

class TimelineGetApiTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testTimelineGetApiWithEmptyFormData()
    {
        $response = $this->json('POST', '/api/v1/timeline/get', []);
        $response->assertStatus(422);
    }

    /**
     * Check Api with correct values
     */
    public function testGetApiWithCorrectValues()
    {
        $response = $this->json('POST', '/api/v1/timeline/get', [
            'topic_num' => 238, 
            'asofdate' => time(), 
            'algorithm' => 'blind_popularity',
            'tracing' => 1
        ]);
        if ($response->status() === 500) {
            fwrite(STDERR, json_encode($response->json(), JSON_PRETTY_PRINT));
        }
        $response->assertStatus(200);
    }

    public function testWithCorrectValuesForValidResponseStructure()
    {
        $response = $this->json('POST', '/api/v1/timeline/get', [
            'topic_num' => 238, 
            'asofdate' => time(), 
            'algorithm' => 'blind_popularity'
        ]);
        $response->assertJsonStructure([
            "status_code",
            "message",
            "error",
            "data" => [
                "topic_name",
                "timeline" => [
                    "*" => [
                        "as_of_date",
                        "event"
                    ]
                ]
            ]
        ]);
    }
}
