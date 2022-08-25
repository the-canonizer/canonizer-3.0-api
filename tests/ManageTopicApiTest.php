<?php

use App\Models\User;

class ManageTopicApiTest extends TestCase
{
     /**
     * Check Api with empty form data
     * validation
     */
    public function testManageTopicApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/manage-topic', []);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testManageTopicApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "topic_id" => "",
            "nick_name" => "",
            "topic_name" => "",
            "submitter" => "",
            "namespace_id" => "",
            "note" => "",
            "event_type" => "",
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/manage-topic', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testManageTopicApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => "1",
            "topic_id" => "1",
            "nick_name" => "1",
            "topic_name" => "1",
            "submitter" => "1",
            "namespace_id" => "1",
            "note" => "1",
            "event_type" => "533",
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/manage-topic', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }


     /**
     * Check Api with valid data
     * validation
     */
    public function testUpdateManageTopicWithValidData()
    {
        $validData = [
            "topic_num" => "1",
            "topic_id" => "1",
            "nick_name" => "12",
            "topic_name" => "1211111",
            "submitter" => "1",
            "namespace_id" => "1",
            "note" => "1",
            "event_type" => "update",
        ];
        print sprintf("Test with valid values for updating camp based on a version");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/manage-topic', $validData);
        $this->assertEquals(200,  $this->response->status());
    }

     /**
     * Check Api with valid data
     * validation
     */
    public function testObjectionManageTopicWithValidData()
    {

        $validData = [
            "topic_num" => "1",
            "topic_id" => "1",
            "nick_name" => "12",
            "topic_name" => "1211111",
            "submitter" => "1",
            "namespace_id" => "1",
            "note" => "1",
            "event_type" => "objection",
            "objection_reason" => "reason",
        ];
        print sprintf("Test with valid values for objecting a camp");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/manage-topic', $validData);
        $this->assertEquals(200,  $this->response->status());
    }

         /**
     * Check Api with valid data
     * validation
     */
    public function testEditManageTopicWithValidData()
    {
        $validData = [
            "topic_num" => "1",
            "topic_id" => "1",
            "nick_name" => "12",
            "topic_name" => "1211111",
            "submitter" => "1",
            "namespace_id" => "1",
            "note" => "1",
            "event_type" => "edit"
        ];
        print sprintf("Test with valid values for editing a camp");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/manage-topic', $validData);
        $this->assertEquals(200,  $this->response->status());
    }


    /**
     * Check Api without auth
     * validation
     */
    public function testManageTopicApiWithoutAuth()
    {
        $this->post('/api/v3/manage-topic', []);
        $this->assertEquals(401,  $this->response->status());
    }
}