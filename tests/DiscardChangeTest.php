<?php

namespace Tests;

use App\Models\User;

class DiscardChangeTest extends TestCase
{
    /**
     * Check Api without payload
     * validation
     */
    public function testDiscardChangeWithoutPayload()
    {
        $payload = [];
        $user = User::factory()->make();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $_res = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with empty form data
     * validation
     */
    public function testDiscardChangeWithEmptyFormData()
    {
        $payload = [
            "id" => "",
            "type" => "",
        ];
        $user = User::factory()->make();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $_res = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with wrong type 
     * validation
     */
    public function testDiscardChangeWithWrongType()
    {
        $payload = [
            "id" => 123,
            "type" => "HelloWorld",
        ];
        $user = User::factory()->make();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $_res = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with wrong data 
     * validation
     */
    public function testDiscardChangeWithWrongData()
    {
        $payload = [
            "id" => 123,
            "type" => "statement",
        ];
        $user = User::factory()->make();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $_res = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with data 
     * validation
     */
    public function testDiscardChangeForStatementWithValidData()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "1",
            "statement" => "testDiscardChange",
            "event_type" => "create",
        ];
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData, $header);

        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "47",
            "camp_num" => "1"
        ];
        $_res = $this->actingAs($user)->post('/api/v3/get-statement-history', $validData, $header);
        $response = $_res->getData();

        $payload = [
            "id" => $response->data->items[0]->id,
            "type" => "statement",
        ];

        $_res = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $_res->assertStatus(200);
    }
    
    public function testDiscardChangeForTopicWithValidData()
    {

        $validData = [
            "topic_num" => "1",
            "topic_id" => "1",
            "nick_name" => "347",
            "topic_name" => rand(),
            "submitter" => "1",
            "namespace_id" => "1",
            "note" => "1",
            "event_type" => "update",
        ];
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $_res = $this->actingAs($user)->post('/api/v3/manage-topic', $validData);
    
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "1",
            "type" => "all",
        ];
        $_res = $this->actingAs($user)->post('/api/v3/get-topic-history', $validData ,$header);
        $response = $_res->getData();

        $payload = [
            "id" => $response->data->items[0]->id,
            "type" => "topic",
        ];

        $_res = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $_res->assertStatus(200);
    }
    
    public function testDiscardChangeForCampWithValidData()
    {

        $validData = [
            "topic_num" => "47",
            "camp_num" => "2",
            "camp_id" => "2",
            "camp_name" => rand(),
            "nick_name" => "347",
            "camp_about_nick_id" => "123",
            "note" => "note",
            "submitter" => "1",
            "event_type" => "update",
        ];
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,  
        ];
        $_res = $this->actingAs($user)->post('/api/v3/manage-camp', $validData);
        
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "47",
            "camp_num" => "2",
            "type" => "all",
        ];
        $_res = $this->actingAs($user)->post('/api/v3/get-camp-history', $validData ,$header);
        $response = $_res->getData();

        $payload = [
            "id" => $response->data->items[0]->id,
            "type" => "camp",
        ];

        $_res = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $_res->assertStatus(200);
    }
}
