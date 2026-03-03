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
        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->json('POST', '/api/v1/tree/store', []);
        $response->assertStatus(422);
    }

    /**
     * Check Api with empty values
     */
    public function testStoreApiWithEmptyValues()
    {
        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->json('POST', '/api/v1/tree/store', [
            'topic_num' => '',
            'asofdate' => '',
            'algorithm' => '',
            'update_all' => ''
        ]);
        $response->assertStatus(422);
    }

    /**
     * Check Api with correct values
     */
    public function testStoreApiWithCorrectValues()
    {
        $user = \App\Models\User::factory()->create();
        $topic = \App\Models\Topic::factory()->create();
        
        $response = $this->actingAs($user)->json('POST', '/api/v1/tree/store', [
            'topic_num' => $topic->topic_num,
            'asofdate' => time(),
            'algorithm' => 'blind_popularity',
            'update_all' => 0
        ]);
        $response->assertStatus(200);
    }
}
