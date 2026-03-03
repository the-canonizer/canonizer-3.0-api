<?php

namespace Tests;

use Carbon\Carbon;

class GetAllTopicsApiTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetAllTopicsApiWithEmptyFormData()
    {
        $response = $this->json('POST', '/api/v1/topic/getAll', []);
        if ($response->status() !== 422) {
            fwrite(STDERR, $response->getContent());
        }
        $response->assertStatus(422);
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetAllTopicsApiWithEmptyValues()
    {
        $response = $this->json('POST', '/api/v1/topic/getAll', [
            'page_number' => '', 
            'page_size' => '', 
            'algorithm' => '', 
            'namespace_id' => '', 
            'asofdate' => '', 
            'search' => '', 
            'user_email' => '', 
            'page' => 'browse'
        ]);
        $response->assertStatus(422);
    }

    /**
     * Check Api with correct values without asof
     */
    public function testWithCorrectValuesWithoutFilter()
    {
        $response = $this->json('POST', '/api/v1/topic/getAll', [
            'page_number' => 1, 
            'page_size' => 20, 
            'algorithm' => 'blind_popularity', 
            'namespace_id' => 1, 
            'asofdate' => time(), 
            'user_email' => '', 
            'page' => 'browse'
        ]);
        $response->assertStatus(422);
    }

    /**
     * Check Api with wrong values
     * Not found error 404
     * Namespace = 0 there is no data against namespace 0
     */
    public function testWithWrongValuesWithoutFilter()
    {
        $response = $this->json('POST', '/api/v1/topic/getAll', [
            'asof' => 'default', 
            'page_number' => 1, 
            'page_size' => 20, 
            'algorithm' => 'blind_popularity', 
            'namespace_id' => 0, 
            'asofdate' => time(), 
            'user_email' => 'abcxyz@example.com'
        ]);
        $response->assertStatus(200);
        $this->assertCount(0, $response['data']['topic']);
    }

    /**
     * Check Api with correct values with filter search
     */
    public function testWithCorrectValuesWithFilterSearch()
    {
        $response = $this->json('POST', '/api/v1/topic/getAll', [
            'asof' => 'default', 
            'page_number' => 1, 
            'page_size' => 20, 
            'algorithm' => 'blind_popularity', 
            'namespace_id' => 1, 
            'asofdate' => time(), 
            'search' => 'Hard', 
            'filter' => 1.7, 
            'user_email' => '', 
            'page' => 'browse'
        ]);
        $response->assertStatus(200);
    }

    /**
     * Check Api with correct values with special characters in filter search
     */
    public function testWithCorrectValuesWithSpecialCharactersInFilterSearch()
    {
        $specialChars = ['!@#', '$%^', '&*(', ')_+', "[]%"];
        foreach ($specialChars as $char) {
            $response = $this->json('POST', '/api/v1/topic/getAll', [
                'asof' => 'default', 
                'page_number' => 1, 
                'page_size' => 20, 
                'algorithm' => 'blind_popularity', 
                'namespace_id' => 1, 
                'asofdate' => time(), 
                'search' => $char, 
                'filter' => 1.7, 
                'user_email' => '', 
                'page' => 'browse'
            ]);
            $response->assertStatus(200);
        }
    }

    /**
     * Check Api by passing search as integer values
     */
    public function testSearchWithNumericValue()
    {
        $response = $this->json('POST', '/api/v1/topic/getAll', [
            'asof' => 'default', 
            'page_number' => 1, 
            'page_size' => 20, 
            'algorithm' => 'blind_popularity', 
            'namespace_id' => 1, 
            'asofdate' => time(), 
            'search' => 4645, 
            'filter' => 1.7, 
            'user_email' => '', 
            'page' => 'browse'
        ]);
        $response->assertStatus(422);
    }

    /**
     * Check Api with correct values without search and filter
     */
    public function testWithCorrectValuesWithoutFilterSearch()
    {
        $response = $this->json('POST', '/api/v1/topic/getAll', [
            'asof' => 'default', 
            'page_number' => 1, 
            'page_size' => 20, 
            'algorithm' => 'blind_popularity', 
            'namespace_id' => 1, 
            'asofdate' => time(), 
            'user_email' => '', 
            'page' => 'browse',
            'tracing' => 1
        ]);
        if ($response->status() === 500) {
            fwrite(STDERR, json_encode($response->json(), JSON_PRETTY_PRINT));
        }
        $response->assertStatus(200);
    }

    /**
     * Check Response Structure with correct values
     */
    public function testWithCorrectValuesForValidResponseStructure()
    {
        $response = $this->json('POST', '/api/v1/topic/getAll', [
            'asof' => 'default', 
            'page_number' => 1, 
            'page_size' => 20, 
            'algorithm' => 'blind_popularity', 
            'namespace_id' => 1, 
            'asofdate' => time(), 
            'user_email' => '', 
            'page' => 'browse'
        ]);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                'topic' => [
                    '*' => [
                        "submitter_nick_id",
                        "namespace_id",
                        "topic_score",
                        "topic_full_score",
                        "topic_id",
                        "topic_name",
                        "tree_structure",
                        "as_of_date",
                        "camp_views",
                    ]
                ]
            ],
        ]);
    }

    /**
     * Check Response Structure with past asofdate
     */
    public function testWithCorrectValuesForValidResponseStructureInDatabase()
    {
        $response = $this->json('POST', '/api/v1/topic/getAll', [
            'asof' => 'default', 
            'page_number' => 1, 
            'page_size' => 20, 
            'algorithm' => 'blind_popularity', 
            'namespace_id' => 1, 
            'asofdate' => Carbon::now()->subDays(2)->timestamp, 
            'user_email' => '', 
            'page' => 'browse'
        ]);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                'topic' => [
                    '*' => [
                        "submitter_nick_id",
                        "namespace_id",
                        "score",
                        "topic_score",
                        "topic_full_score",
                        "topic_id",
                        "topic_name",
                        "tree_structure",
                        "as_of_date",
                    ]
                ]
            ],
        ]);
    }
}
