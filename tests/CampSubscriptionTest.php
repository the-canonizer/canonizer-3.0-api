<?php

use App\Models\Camp;
use App\Models\User;

class CampSubscriptionTest extends TestCase
{
    /**
     * Check Api without payload
    */
    public function testCampSubscriptionApiWithoutPayload() {
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/camp/subscription', []);
        $this->assertEquals(400,  $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/camp/subscription', $emptyData);
        $this->assertEquals(400, $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/camp/subscription', $invalidData);
        $this->assertEquals(400,  $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/camp/subscription', $validData);
        
        $this->assertEquals(200,  $this->response->status());

        /// Unit test for the un-subscription on above subscription ...
        if($this->response->getData()->status_code == 200) {
            // Update the payload ...
            $validData["subscription_id"] = $this->response->getData()->data->subscriptionId;
            $validData["checked"] = false;

            $user = User::factory()->make();
            $this->actingAs($user)->post('/api/v3/camp/subscription', $validData);
            $this->assertEquals(200,  $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/camp/subscription', $invalidData);
        $this->assertEquals(400,  $this->response->status());
    }

    /**
     * Check subscription without auth
    */
    public function  testCampSubscriptionApiWithoutUserAuth()
    {
        $this->post('/api/v3/camp/subscription', []);
        $this->assertEquals(401,  $this->response->status());
    }

    /**
     * Check subscription listing without user auth
    */
    public function testGetCampSubscriptionListWithoutUserAuth() {
        $response = $this->call('GET', '/api/v3/camp/subscription/list/');
        $this->assertEquals(401, $response->status()); 
    }

    /**
    * Check subscription listing without user auth
    */
    public function testGetCampSubscriptionListValidData() {
        $camp = Camp::factory()->make();

        $this->actingAs($camp)->get('/api/v3/camp/subscription/list?page=1&per_page=10');
        $this->assertEquals(200, $this->response->status());
    }
}
