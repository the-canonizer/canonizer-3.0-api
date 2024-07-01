<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\User;

class SiblingCampsApiTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
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
        $this->actingAs($user)->post('/api/v3/get-sibling-camps', [] ,$header);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty form values
     * validation
     */
    public function testGetSiblingCampsApiWithEmptyValues() {
        $emptyData = [
            "parent_camp_num" => "",
            "topic_num" => "",
            "camp_num" => "",
        ];
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-sibling-camps', $emptyData ,$header);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testGetSiblingCampsApiWithInvalidData() {
        $invalidData = [
            "parent_camp_num" => "dd",
            "topic_num" => "df",
            "camp_num" => "ds",
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-sibling-camps', $invalidData ,$header);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with valid data
     * validation
     */
    public function testGetSiblingCampsApiWithValidData() {
        $validData = [
            "parent_camp_num" => 1,
            "topic_num" => 1310,
            "camp_num" => 3,
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-sibling-camps', $validData ,$header);
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api response code with valid data
     * validation
     */
    public function testGetSiblingCampsApiResponseWithValidData() {
        $validData = [
            "parent_camp_num" => 1,
            "topic_num" => 1310,
            "camp_num" => 3,
        ];
        print sprintf("Test with valid values");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-sibling-camps', $validData ,$header);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'topic_num',
                    'camp_num',
                    'camp_name',
                    'submit_time',
                    'go_live_time',
                    'namespace',
                    'namespace_id',
                    'views',
                    'statement',
                    'supporterData' => [],
                ]
            ]
        ]);
    }
}
