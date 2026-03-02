<?php

namespace Tests;

use App\Models\Statement;
use App\Models\User;
use App\Models\Support;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StoreStatementApiTest extends TestCase
{

    use DatabaseTransactions;
    
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
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', []);
        $_res->assertStatus(400);
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
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $emptyData);
        $_res->assertStatus(400);
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
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $invalidData);
        $_res->assertStatus(400);
    }


     /**
     * Check Api with valid data
     * validation
     */
    public function testUpdateStatementApiWithValidData()
    {
        $validData = [
            "topic_num" => "200",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "1",
            "statement" => "statement",
            "event_type" => "update",
            "statement_id" => 1,
        ];
        print sprintf("Test with valid values for updating statement based on a version");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        // dd($_res);
        $_res->assertStatus(200);
    }

     /**
     * Check Api with valid data
     * validation
     */
    public function testCreateStatementApiWithValidData()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "1",
            "statement" => "statement",
            "event_type" => "create",
        ];
        print sprintf("Test with valid values for creating a statement");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $_res->assertStatus(200);
    }

    /**
     * Check Api with valid data & support added after change is submitted
     * validation
     */
    public function testObjectionStatementApiWithValidDataAfterChangeIsSubmitted()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "1",
            "statement" => "statement",
            "event_type" => "objection",
            "objection_reason" => "reason",
            "statement_id" => "4952",
        ];

        $statement = Statement::find($validData['statement_id']);

        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);

        Support::insert([
            'nick_name_id' => 347,
            'delegate_nick_name_id' => 0,
            'topic_num' => 47,
            'camp_num'  =>  1,
            'support_order' =>  1,
            'start' => $statement->submit_time - 10,
            'end' => 0,
        ]);

        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $_res->assertStatus(200); // Change 400 to 200 due to test objection
    }

         /**
     * Check Api with valid data
     * validation
     */
    public function testEditStatementApiWithValidData()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "1",
            "statement" => "statement",
            "event_type" => "edit",
            "statement_id" => 1,
        ];
        print sprintf("Test with valid values for editing a statement");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $_res->assertStatus(200);
    }


    /**
     * Check Api without auth
     * validation
     */
    public function testStoreStatementApiWithoutAuth()
    {
        print sprintf("Test with empty form data");
        $_res = $this->post('/api/v3/store-camp-statement', []);
        $_res->assertStatus(401);
    }

    public function testCreateStatementInGracePeriodWithValidData()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "1",
            "statement" => "statement",
            "event_type" => "create",
        ];
        print sprintf("Test with valid values for creating a statement and it should be in grace period");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $_res->assertStatus(200);

        $statement = Statement::where('submitter_nick_id', 347)->orderBy('submit_time', 'desc')->first();
        $this->assertNotNull($statement);
        $this->assertEquals(1, $statement->grace_period);
    }

    public function testCreateDraftStatment()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "347",
            "statement" => "statement",
            "event_type" => "create",
            "is_draft" => true,
        ];
        print sprintf("Test to create draft statement");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $_res->assertStatus(200);

        $statement = Statement::where('submitter_nick_id', 347)->orderBy('submit_time', 'desc')->first();
        $this->assertNotNull($statement);

    }

    public function testEditDraftStatment()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "347",
            "statement" => "statement",
            "event_type" => "create",
            "is_draft" => true,
        ];

        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $statement = $_res->json();
        $draftRecordId = $statement['data']['draft_record_id'];

        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "347",
            "statement" => "statement",
            "statement_id" => $draftRecordId,
            "event_type" => "edit",
            "is_draft" => true,
        ];
        print sprintf("Test to edit draft statement");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $_res->assertStatus(200);
    }

    public function testPublishDraftStatment()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "347",
            "statement" => "statement",
            "event_type" => "create",
            "is_draft" => true,
        ];
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $statement = $_res->json();
        $draftRecordId = $statement['data']['draft_record_id'];

        // Publish a draft statement
        $validData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "347",
            "note" => "note",
            "submitter" => "347",
            "statement" => "statement",
            "statement_id" => $draftRecordId,
            "event_type" => "create",
            "is_draft" => false,
        ];

        $_res = $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);

        $_res->assertStatus(200);
    }
}
