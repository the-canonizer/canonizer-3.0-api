<?php

use App\Models\User;

class TopicTagTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample() {
        $this->assertTrue(true);
    }

    /**
     * Check Api with empty form data
     * validation
     */
    public function testTopicTagListApiWithEmptyFormData() {
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-tags-list', [] ,$header);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty form values
     * validation
     */
    public function testGetTopicHistoryApiWithEmptyValues() {
        $emptyData = [
            "per_page" => "",
            "page" => "",
            "search_term" => "",
            "sort_by" => "",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-tags-list', [] ,$header);
        $this->assertEquals(400, $this->response->status());
    }

        /**
     * Check Api with valid data
     * validation
     */
    public function testGetTopicHistoryApiWithValidData() {
        $validData = [
            "per_page" => "10",
            "page" => "1",
            "search_term" => "",
            "sort_by" => "asc",
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-tags-list', $validData ,$header);
        $this->assertEquals(200, $this->response->status());
    }
}
