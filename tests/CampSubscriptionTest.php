<?php

use App\Models\Camp;
use App\Models\User;

class CampSubscriptionTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testCampSubscriptionApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/camp/subscription', []);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testCampSubscriptionApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "camp_num" => "",
            "checked" => "",
            "subscription_id" => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/camp/subscription', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testCampSubscriptionApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => 2,
            "camp_num" => 1,
            "checked" => "xyz",
            "subscription_id" => "abc"
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/camp/subscription', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api with valid data for subscribing
     * validation
     */
    public function testCampSubscriptionApiWithValidData()
    {
        $invalidData = [
            "topic_num" => 2,
            "camp_num" => 1,
            "checked" => true,
            "subscription_id" => ""
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/camp/subscription', $invalidData);
        $this->assertEquals(200,  $this->response->status());
    }


    /**
     * Check Api with invalid data for unsubscribing
     * validation
     */
    public function testCampSubscriptionApiWithInvalidUnsubscriptionData()
    {
        $invalidData = [
            "topic_num" => 2,
            "camp_num" => 1,
            "checked" => false,
            "subscription_id" => ""
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/camp/subscription', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check Api without auth
     * validation
     */
    public function  testCampSubscriptionApiWithoutUserAuth()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->post('/api/v3/camp/subscription', []);
        $this->assertEquals(401,  $this->response->status());
    }

    public function testGetCampSubscriptionListInvalidData(){
        print sprintf("\n Get camp subscription List Invalid Data %d %s",400, PHP_EOL);
        $response = $this->call('GET', '/api/v3/camp/subscription/list/');
        $this->assertEquals(401, $response->status()); 
    }

    public function testGetCampSubscriptionListValidData(){
        print sprintf(" \n  Get camp subscription List Valid Data %d %s", 200,PHP_EOL);
        $camp = Camp::factory()->make();

        $this->actingAs($camp)
        ->get('/api/v3/camp/subscription/list?page=1&per_page=10');
        $this->assertEquals(200, $this->response->status());
    }
}
