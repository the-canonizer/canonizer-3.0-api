<?php

use App\Models\User;

class StatementDiscardChangeTest extends TestCase
{
    /**
     * Check Api without payload
     * validation
     */
    public function testDiscardChangeWithoutPayload()
    {
        $payload = [];
        print sprintf("Test without payload");
        $user = User::factory()->make();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $this->assertEquals(400, $this->response->status());
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
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $this->assertEquals(400, $this->response->status());
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
        print sprintf("Test with wrong type");
        $user = User::factory()->make();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $this->assertEquals(400, $this->response->status());
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
        print sprintf("Test with wrong data");
        $user = User::factory()->make();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,

        ];
        $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with data 
     * validation
     */
    public function testDiscardChangeWithValidData()
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
        $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData, $header);

        $validData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => "47",
            "camp_num" => "1"
        ];
        $this->actingAs($user)->post('/api/v3/get-statement-history', $validData, $header);
        $response = $this->response->getData();

        $payload = [
            "id" => $response->data->items[0]->id,
            "type" => "statement",
        ];

        print sprintf("Test with valid data");
        $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $this->assertEquals(200, $this->response->status());
    }
}
