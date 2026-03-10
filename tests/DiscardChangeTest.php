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
        $response = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $response->assertStatus(400);
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
        $response = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $response->assertStatus(400);
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
        $response = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $response->assertStatus(400);
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
        $response = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $response->assertStatus(400);
    }

    /**
     * Check Api with data 
     * validation
     */
    public function testDiscardChangeForStatementWithValidData()
    {
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create(['submitter_nick_id' => $nickname->id]);
        $camp = \App\Models\Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'camp_num' => 1,
            'camp_name' => 'Agreement',
            'parent_camp_num' => 0,
            'submitter_nick_id' => $nickname->id
        ]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $user->id,
            "statement" => "testDiscardChange",
            "event_type" => "create",
        ];
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ];
        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData, $header);
        if ($response->status() !== 200) {
            fwrite(STDOUT, "store-camp-statement failed: " . $response->getContent() . "\n");
        }
        $response->assertStatus(200);

        $historyData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num
        ];
        $response = $this->actingAs($user)->post('/api/v3/get-statement-history', $historyData, $header);
        $responseData = $response->getData();
        if ($response->status() !== 200 || !isset($responseData->data->items[0])) {
            fwrite(STDOUT, "get-statement-history failed or empty: " . json_encode($responseData) . "\n");
        }

        $payload = [
            "id" => $responseData->data->items[0]->id,
            "type" => "statement",
        ];

        $response = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        if ($response->status() !== 200) {
            fwrite(STDOUT, "discard/change (statement) failed: " . $response->getContent() . "\n");
        }
        $response->assertStatus(200);
    }
    
    public function testDiscardChangeForTopicWithValidData()
    {
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create(['submitter_nick_id' => $nickname->id]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "topic_id" => $topic->id,
            "nick_name" => $nickname->id,
            "topic_name" => "Updated Topic " . uniqid(),
            "submitter" => $user->id,
            "namespace_id" => 1,
            "note" => "note",
            "event_type" => "update",
        ];
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ];
        $response = $this->actingAs($user)->post('/api/v3/manage-topic', $validData, $header);
        if ($response->status() !== 200) {
            fwrite(STDOUT, "manage-topic failed: " . $response->getContent() . "\n");
        }
        $response->assertStatus(200);
    
        $historyData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => $topic->topic_num,
            "type" => "all",
        ];
        $response = $this->actingAs($user)->post('/api/v3/get-topic-history', $historyData ,$header);
        $responseData = $response->getData();
        if ($response->status() !== 200 || !isset($responseData->data->items[0])) {
            fwrite(STDOUT, "get-topic-history failed or empty: " . json_encode($responseData) . "\n");
        }

        $payload = [
            "id" => $responseData->data->items[0]->id,
            "type" => "topic",
        ];

        $response = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        $response->assertStatus(200);
    }
    
    public function testDiscardChangeForCampWithValidData()
    {
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create(['submitter_nick_id' => $nickname->id]);
        $camp = \App\Models\Camp::factory()->create([
            'topic_num' => $topic->topic_num,
            'camp_num' => 1,
            'camp_name' => 'Agreement',
            'parent_camp_num' => 0,
            'submitter_nick_id' => $nickname->id
        ]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num,
            "camp_id" => $camp->id,
            "camp_name" => ($camp->camp_num == 1) ? "Agreement" : ("Updated Camp " . uniqid()),
            "nick_name" => $nickname->id,
            "camp_about_nick_id" => $nickname->id,
            "note" => "note",
            "submitter" => $user->id,
            "event_type" => "update",
        ];
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,  
        ];
        $response = $this->actingAs($user)->post('/api/v3/manage-camp', $validData, $header);
        if ($response->status() !== 200) {
            fwrite(STDOUT, "manage-camp failed: " . $response->getContent() . "\n");
        }
        $response->assertStatus(200);
        
        $historyData = [
            "per_page" => "10",
            "page" => "1",
            "topic_num" => $topic->topic_num,
            "camp_num" => $camp->camp_num,
            "type" => "all",
        ];
        $response = $this->actingAs($user)->post('/api/v3/get-camp-history', $historyData ,$header);
        $responseData = $response->getData();
        if ($response->status() !== 200 || !isset($responseData->data->items[0])) {
            fwrite(STDOUT, "get-camp-history failed or empty: " . json_encode($responseData) . "\n");
        }

        $payload = [
            "id" => $responseData->data->items[0]->id,
            "type" => "camp",
        ];

        $response = $this->actingAs($user)->post('/api/v3/discard/change', $payload, $header);
        if ($response->status() !== 200) {
            fwrite(STDOUT, "discard/change (camp) failed: " . $response->getContent() . "\n");
        }
        $response->assertStatus(200);
    }
}
