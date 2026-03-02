<?php

namespace Tests;

use App\Models\Camp;
use App\Models\User;

class CampSubscriptionTest extends TestCase
{
    /**
     * Check Api without payload
    */
    public function testCampSubscriptionApiWithoutPayload() {
        $user = User::factory()->make();
        $_res = $this->actingAs($user)->post('/api/v3/camp/subscription', []);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with empty payload values
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
        $user = User::factory()->make();
        $_res = $this->actingAs($user)->post('/api/v3/camp/subscription', $emptyData);
        $_res->assertStatus(400);
    }

    /**
        * Check Api with invalid payload data types
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
        $user = User::factory()->make();
        $_res = $this->actingAs($user)->post('/api/v3/camp/subscription', $invalidData);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with valid data for subscribing
     * 
    */
    public function testCampSubscribeAndUnSubscribeWithValidData () {
        $validData = [
            "topic_num" => 2,
            "camp_num" => 1,
            "checked" => true,
            "subscription_id" => ""
        ];
        $user = User::factory()->make();
        $_res = $this->actingAs($user)->post('/api/v3/camp/subscription', $validData);
        
        $_res->assertStatus(200);

        /// Unit test for the un-subscription on above subscription ...
        if($_res->getData()->status_code == 200) {
            // Update the payload ...
            $validData["subscription_id"] = $_res->getData()->data->subscriptionId;
            $validData["checked"] = false;

            $user = User::factory()->make();
            $_res = $this->actingAs($user)->post('/api/v3/camp/subscription', $validData);
            $_res->assertStatus(200);
        }
    }


    /**
     * Check Un-subscribe with invalid data -- without subscription id
     */
    public function testCampUnSubscriptionByInvalidData() {
        $invalidData = [
            "topic_num" => 2,
            "camp_num" => 1,
            "checked" => false,
            "subscription_id" => ""
        ];
        $user = User::factory()->make();
        $_res = $this->actingAs($user)->post('/api/v3/camp/subscription', $invalidData);
        $_res->assertStatus(400);
    }

    /**
     * Check subscription without auth
    */
    public function  testCampSubscriptionApiWithoutUserAuth()
    {
        $_res = $this->post('/api/v3/camp/subscription', []);
        $_res->assertStatus(401);
    }

    /**
     * Check subscription listing without user auth
    */
    public function testGetCampSubscriptionListWithoutUserAuth() {
        $response = $this->call('GET', '/api/v3/camp/subscription/list/');
        $_res->assertStatus(401); 
    }

    /**
    * Check subscription listing without user auth
    */
    public function testGetCampSubscriptionListValidData() {
        $camp = Camp::factory()->make();

        $_res = $this->actingAs($camp)->get('/api/v3/camp/subscription/list?page=1&per_page=10');
        $_res->assertStatus(200);
    }
}
