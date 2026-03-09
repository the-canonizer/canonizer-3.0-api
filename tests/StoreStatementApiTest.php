<?php

namespace Tests;

use App\Models\Statement;
use App\Models\User;
use App\Models\Support;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StoreStatementApiTest extends TestCase
{

    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Bus::fake();
    }
    
    /**
     * Check Api with empty form data
     * validation
     */
    public function testStoreStatementApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', []);
        $response->assertStatus(400);
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testStoreStatementApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "camp_num" => "",
            "nick_name" => "347",
            "note" => "",
            "submitter" => "",
            "statement" => "",
        ];
        print sprintf("Test with empty values");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $emptyData);
        $response->assertStatus(400);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testStoreStatementApiWithInvalidData() 
    {
        $invalidData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "533",
            "note" => "note",
            "submitter" => "1",
            "objection" => "1",
        ];
        print sprintf("Test with invalid values");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $invalidData);
        $response->assertStatus(400);
    }


     /**
     * Check Api with valid data
     * validation
     */
    public function testUpdateStatementApiWithValidData()
    {
        print sprintf("Test with valid values for updating statement based on a version");
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create();
        $camp = \App\Models\Camp::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1]);
        $statement = \App\Models\Statement::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1, 'submitter_nick_id' => $nickname->id]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $nickname->id,
            "statement" => "new statement",
            "event_type" => "update",
            "statement_id" => $statement->id,
        ];

        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $response->assertStatus(200);
    }

     /**
     * Check Api with valid data
     * validation
     */
    public function testCreateStatementApiWithValidData()
    {
        print sprintf("Test with valid values for creating a statement");
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create();
        $camp = \App\Models\Camp::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $nickname->id,
            "statement" => "statement",
            "event_type" => "create",
        ];

        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $response->assertStatus(200);
    }

    /**
     * Check Api with valid data & support added after change is submitted
     * validation
     */
    public function testObjectionStatementApiWithValidDataAfterChangeIsSubmitted()
    {
        $user = \App\Models\User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);

        $topic = \App\Models\Topic::factory()->create();
        $camp = \App\Models\Camp::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $nickname->id,
            "statement" => "statement",
            "event_type" => "objection",
            "objection_reason" => "reason",
        ];

        $statement = new Statement();
        $statement->topic_num = $topic->topic_num;
        $statement->camp_num = 1;
        $statement->submitter_nick_id = $nickname->id;
        $statement->value = "statement";
        $statement->parsed_value = "statement";
        $statement->note = "note";
        $statement->submit_time = time();
        $statement->go_live_time = time() + 1000;
        $statement->grace_period = 0;
        $statement->save();

        $validData['statement_id'] = $statement->id;

        Support::insert([
            'nick_name_id' => $nickname->id,
            'delegate_nick_name_id' => 0,
            'topic_num' => $topic->topic_num,
            'camp_num'  =>  1,
            'support_order' =>  1,
            'start' => $statement->submit_time - 10,
            'end' => 0,
        ]);

        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        // echo "\nResponse: " . $response->getContent() . "\n";
        $response->assertStatus(200); // Change 400 to 200 due to test objection
    }

         /**
     * Check Api with valid data
     * validation
     */
    public function testEditStatementApiWithValidData()
    {
        print sprintf("Test with valid values for editing a statement");
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create();
        $camp = \App\Models\Camp::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1]);
        $statement = \App\Models\Statement::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1, 'submitter_nick_id' => $nickname->id]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $nickname->id,
            "statement" => "edited statement",
            "event_type" => "edit",
            "statement_id" => $statement->id,
        ];

        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $response->assertStatus(200);
    }


    /**
     * Check Api without auth
     * validation
     */
    public function testStoreStatementApiWithoutAuth()
    {
        print sprintf("Test with empty form data");
        $response = $this->post('/api/v3/store-camp-statement', []);
        $response->assertStatus(401);
    }

    public function testCreateStatementInGracePeriodWithValidData()
    {
        print sprintf("Test with valid values for creating a statement and it should be in grace period");
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create();
        $camp = \App\Models\Camp::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $nickname->id,
            "statement" => "statement",
            "event_type" => "create",
        ];

        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $response->assertStatus(200);

        $statement = Statement::where('submitter_nick_id', $nickname->id)->orderBy('submit_time', 'desc')->first();
        $this->assertNotNull($statement);
        $this->assertEquals(1, $statement->grace_period);
    }

    public function testCreateDraftStatment()
    {
        print sprintf("Test to create draft statement");
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create();
        $camp = \App\Models\Camp::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $nickname->id,
            "statement" => "statement",
            "event_type" => "create",
            "is_draft" => true,
        ];
        
        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $response->assertStatus(200);

        $statement = Statement::where('submitter_nick_id', $nickname->id)->orderBy('submit_time', 'desc')->first();
        $this->assertNotNull($statement);
    }

    public function testEditDraftStatment()
    {
        print sprintf("Test to edit draft statement");
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create();
        $camp = \App\Models\Camp::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $nickname->id,
            "statement" => "draft statement",
            "event_type" => "create",
            "is_draft" => true,
        ];

        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $statement = $response->json();
        $draftRecordId = $statement['data']['draft_record_id'];

        $editData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "edited note",
            "submitter" => $nickname->id,
            "statement" => "edited draft statement",
            "statement_id" => $draftRecordId,
            "event_type" => "edit",
            "is_draft" => true,
        ];
        
        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $editData);
        $response->assertStatus(200);
    }

    public function testPublishDraftStatment()
    {
        print sprintf("Test to publish draft statement");
        $user = User::factory()->create();
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $topic = \App\Models\Topic::factory()->create();
        $camp = \App\Models\Camp::factory()->create(['topic_num' => $topic->topic_num, 'camp_num' => 1]);

        $validData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $nickname->id,
            "statement" => "draft statement",
            "event_type" => "create",
            "is_draft" => true,
        ];

        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $statement = $response->json();
        $draftRecordId = $statement['data']['draft_record_id'];

        // Publish a draft statement
        $publishData = [
            "topic_num" => $topic->topic_num,
            "camp_num" => 1,
            "nick_name" => $nickname->id,
            "note" => "note",
            "submitter" => $nickname->id,
            "statement" => "published statement",
            "statement_id" => $draftRecordId,
            "event_type" => "create",
            "is_draft" => false,
        ];

        $response = $this->actingAs($user)->post('/api/v3/store-camp-statement', $publishData);
        $response->assertStatus(200);
    }
}
