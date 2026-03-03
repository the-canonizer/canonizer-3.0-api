<?php

namespace Tests;

class TreeGetApiTest extends TestCase
{
    protected $topic;

    public function setUp(): void
    {
        parent::setUp();
        $this->topic = \App\Models\Topic::factory()->create();
        \App\Models\Camp::factory()->create(['topic_num' => $this->topic->topic_num, 'camp_num' => 1]);
    }
    /**
     * Check Api with empty form data
     * validation
     */
    public function testTreeGetApiWithEmptyFormData()
    {
        $response = $this->json('POST', '/api/v1/tree/get', []);
        $response->assertStatus(422);
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testTreeGetApiWithEmptyValues()
    {
        $response = $this->json('POST', '/api/v1/tree/get', [
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
    public function testGetApiWithCorrectValues()
    {
        $response = $this->json('POST', '/api/v1/tree/get', [
            'topic_num' => $this->topic->topic_num, 
            'asofdate' => time(), 
            'algorithm' => 'blind_popularity', 
            'update_all' => 0, 
            "view" => "\$QWZhYWNiVmhtMTE2ekt3Vg\$iIc0UGbTCDIgCXXwpHnzXA"
        ]);
        $response->assertStatus(200);
    }

    public function testWithCorrectValuesForValidResponseStructure()
    {
        $response = $this->json('POST', '/api/v1/tree/get', [
            'topic_num' => $this->topic->topic_num, 
            'asofdate' => time(), 
            'algorithm' => 'blind_popularity', 
            'update_all' => 0
        ]);
        $response->assertJsonStructure([
            "status_code",
            "message",
            "error",
            'data' => [
                0 => [
                    '1' => [
                        "topic_id",
                        "camp_id",
                        "title",
                        "review_title",
                        "link",
                        "review_link",
                        "score",
                        "full_score",
                        "submitter_nick_id",
                        "created_date",
                        "is_valid_as_of_time",
                        "is_disabled",
                        "is_one_level",
                        "is_archive",
                        "direct_archive",
                        "subscribed_users",
                        "support_tree",
                        "children",
                        "collapsedTreeCampIds",
                        "camp_views"
                    ]
                ]
            ],
        ]);
    }

    /**
     * Test invalid topic_num value type
     */
    public function testInvalidTopicNumValueType()
    {
        $response = $this->json('POST', '/api/v1/tree/get', [
            'topic_num' => 'invalid',
            'asofdate' => time(),
            'algorithm' => 'blind_popularity',
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test missing required fields
     */
    public function testMissingRequiredFields()
    {
        $response = $this->json('POST', '/api/v1/tree/get', [
            'asofdate' => time(),
            'algorithm' => 'blind_popularity',
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test invalid model_type value
     */
    public function testInvalidModelTypeValue()
    {
        $response = $this->json('POST', '/api/v1/tree/get', [
            'topic_num' => $this->topic->topic_num,
            'asofdate' => time(),
            'algorithm' => 'blind_popularity',
            'model_type' => 'invalid_type',
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test update_all with invalid value
     */
    public function testInvalidUpdateAllValue()
    {
        $response = $this->json('POST', '/api/v1/tree/get', [
            'topic_num' => $this->topic->topic_num,
            'asofdate' => time(),
            'algorithm' => 'blind_popularity',
            'update_all' => 5,
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test non-existing topic_num
     */
    public function testNonExistingTopicNum()
    {
        $response = $this->json('POST', '/api/v1/tree/get', [
            'topic_num' => 99999999,
            'asofdate' => time(),
            'algorithm' => 'blind_popularity',
        ]);
        $response->assertStatus(404);
    }
}
