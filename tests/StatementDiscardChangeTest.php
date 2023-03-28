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
        $this->actingAs($user)->post('/api/v3/discard/change', $payload ,$header);
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
        $this->actingAs($user)->post('/api/v3/discard/change', $payload ,$header);
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
        $this->actingAs($user)->post('/api/v3/discard/change', $payload ,$header);
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
        $this->actingAs($user)->post('/api/v3/discard/change', $payload ,$header);
        $this->assertEquals(400, $this->response->status());
    }
}
