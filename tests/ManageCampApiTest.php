<?php

use App\Models\Camp;
use App\Models\User;
use App\Models\Support;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ManageCampApiTest extends TestCase
{
    use DatabaseTransactions;
     /**
     * Check Api with empty form data
     * validation
     */
    public function testManageCampApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $this->actingAs($user)->post('/api/v3/manage-camp', []);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testManageCampApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "camp_num" => "",
            "nick_name" => "",
            "note" => "",
            "submitter" => "",
            "camp_id" => "2",
            "camp_name" => rand(),
            "nick_name" => "533",
            "camp_about_nick_id" => "123",
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $this->actingAs($user)->post('/api/v3/manage-camp', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testManageCampApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => "47",
            "camp_num" => "1",
            "nick_name" => "533",
            "note" => "note",
            "submitter" => "1",
            "objection" => "1",
            "event_type" => "objection",
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $this->actingAs($user)->post('/api/v3/manage-camp', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }


     /**
     * Check Api with valid data
     * validation
     */
    public function testUpdateManageCampWithValidData()
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
        print sprintf("Test with valid values for updating camp based on a version");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $this->actingAs($user)->post('/api/v3/manage-camp', $validData);
        $this->assertEquals(200,  $this->response->status());
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testObjectionManageCampWithValidDataAfterChangeIsSubmitted()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "2",
            "camp_id" => "2",
            "camp_name" => rand(),
            "nick_name" => "347",
            "note" => "note",
            "camp_about_nick_id" => "123",
            "submitter" => "1",
            "event_type" => "objection",
            "objection_reason" => "reason",
        ];
        print sprintf("Test with valid values for objecting a camp after the change is submitted");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        Support::insert([
            'nick_name_id' => 347,
            'delegate_nick_name_id' => 0,
            'topic_num' => 47,
            'camp_num'  =>  2,
            'support_order' =>  1,
            'start' => time(),
            'end' => 0,
        ]);
        $this->actingAs($user)->post('/api/v3/manage-camp', $validData);
        $this->assertEquals(200,  $this->response->status()); // Direct supporter can object their own change
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testEditManageCampWithValidData()
    {
        $validData = [
            "topic_num" => "47",
            "camp_num" => "2",
            "nick_name" => "347",
            "camp_about_nick_id" => "123",
            "camp_name" => rand(),
            "camp_id" => "2",
            "note" => "note",
            "submitter" => "1",
            "keywords" => "1",
            "event_type" => "edit",
        ];
        print sprintf("Test with valid values for editing a camp");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $this->actingAs($user)->post('/api/v3/manage-camp', $validData);
        $this->assertEquals(200,  $this->response->status());
    }


    /**
     * Check Api without auth
     * validation
     */
    public function testManageCampApiWithoutAuth()
    {
        $this->post('/api/v3/manage-camp', []);
        $this->assertEquals(401,  $this->response->status());
    }
    
    public function testUpdateManageCampWithValidDataToCheckGracePeriod()
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
        print sprintf("Test with valid values for updating camp & check the change should be in grace period");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $this->actingAs($user)->post('/api/v3/manage-camp', $validData);
        $this->assertEquals(200,  $this->response->status());
        
        $camp = Camp::where('submitter_nick_id', 347)->orderBy('submit_time', 'desc')->first();
        $this->assertNotNull($camp);
        $this->assertEquals(1, $camp->grace_period);
    }
}