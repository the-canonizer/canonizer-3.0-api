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
        $user = User::factory()->create(['status' => 1]);
        $response = $this->actingAs($user)->post('/api/v3/camp/subscription', []);
        $response->assertStatus(400);
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
        $user = User::factory()->create(['status' => 1]);
        $response = $this->actingAs($user)->post('/api/v3/camp/subscription', $emptyData);
        $response->assertStatus(400);
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
        $user = User::factory()->create(['status' => 1]);
        $response = $this->actingAs($user)->post('/api/v3/camp/subscription', $invalidData);
        $response->assertStatus(400);
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
        $user = User::factory()->create(['status' => 1]);
        $response = $this->actingAs($user)->post('/api/v3/camp/subscription', $validData);
        
        $response->assertStatus(200);

        /// Unit test for the un-subscription on above subscription ...
        if($response->getData()->status_code == 200) {
            // Update the payload ...
            $validData["subscription_id"] = $response->getData()->data->subscriptionId;
            $validData["checked"] = false;

            $user = User::factory()->create(['status' => 1]);
            $response = $this->actingAs($user)->post('/api/v3/camp/subscription', $validData);
            $response->assertStatus(200);
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
        $user = User::factory()->create(['status' => 1]);
        $response = $this->actingAs($user)->post('/api/v3/camp/subscription', $invalidData);
        $response->assertStatus(400);
    }

    /**
     * Check subscription without auth
    */
    public function  testCampSubscriptionApiWithoutUserAuth()
    {
        $response = $this->post('/api/v3/camp/subscription', []);
        $response->assertStatus(401);
    }

    /**
     * Check subscription listing without user auth
    */
    public function testGetCampSubscriptionListWithoutUserAuth() {
        $response = $this->get('/api/v3/camp/subscription/list/');
        $response->assertStatus(401); 
    }

    /**
    * Check subscription listing without user auth
    */
    public function testGetCampSubscriptionListValidData() {
        $user = User::factory()->create(['status' => 1]);

        $response = $this->actingAs($user)->get('/api/v3/camp/subscription/list?page=1&per_page=10');
        $response->assertStatus(200);
    }
}
