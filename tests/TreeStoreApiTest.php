<?php

namespace Tests;

class TreeStoreApiTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testStoreApiWithEmptyFormData()
    {
        $response = $this->json('POST', '/api/v1/tree/store', [], [
            'X-Api-Token' => env('API_TOKEN')
        ]);
        $response->assertStatus(422);
    }

    /**
     * Check Api with empty values
     */
    public function testStoreApiWithEmptyValues()
    {
        $response = $this->json('POST', '/api/v1/tree/store', [
            'topic_num' => '',
            'asofdate' => '',
            'algorithm' => '',
            'update_all' => ''
        ], [
            'X-Api-Token' => env('API_TOKEN')
        ]);
        $response->assertStatus(422);
    }

    /**
     * Check Api with correct values
     */
    public function testStoreApiWithCorrectValues()
    {
        $response = $this->json('POST', '/api/v1/tree/store', [
            'topic_num' => 238,
            'asofdate' => time(),
            'algorithm' => 'blind_popularity',
            'update_all' => 0
        ], [
            'X-Api-Token' => env('API_TOKEN')
        ]);
        $response->assertStatus(200);
    }
}
