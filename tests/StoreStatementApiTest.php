<?php

use App\Models\Statement;
use App\Models\User;
use App\Models\Support;
use Laravel\Lumen\Testing\DatabaseTransactions;

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
        $this->actingAs($user)->post('/api/v3/store-camp-statement', []);
        $this->assertEquals(400,  $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/store-camp-statement', $emptyData);
        $this->assertEquals(400, $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/store-camp-statement', $invalidData);
        $this->assertEquals(400,  $this->response->status());
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
        ];
        print sprintf("Test with valid values for updating statement based on a version");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        // dd($this->response);
        $this->assertEquals(200,  $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $this->assertEquals(200,  $this->response->status());
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
            "statement_id" => "1",
        ];

        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);

        Support::insert([
            'nick_name_id' => 347,
            'delegate_nick_name_id' => 0,
            'topic_num' => 47,
            'camp_num'  =>  1,
            'support_order' =>  1,
            'start' => time(),
            'end' => 0,
        ]);
        $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $this->assertEquals(400,  $this->response->status());
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
            "statement_id" => "1",
        ];
        print sprintf("Test with valid values for editing a statement");
           $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $this->assertEquals(200,  $this->response->status());
    }


    /**
     * Check Api without auth
     * validation
     */
    public function testStoreStatementApiWithoutAuth()
    {
        print sprintf("Test with empty form data");
        $this->post('/api/v3/store-camp-statement', []);
        $this->assertEquals(401,  $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/store-camp-statement', $validData);
        $this->assertEquals(200,  $this->response->status());

        $statement = Statement::where('submitter_nick_id', 347)->orderBy('submit_time', 'desc')->first();
        $this->assertNotNull($statement);
        $this->assertEquals(1, $statement->grace_period);
        
    }
}
