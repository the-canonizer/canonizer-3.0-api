<?php

use App\Models\User;
class DeleteNewsFeedApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testDeleteNewsFeedApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/delete-camp-newsfeed', []);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testDeleteNewsFeedApiWithEmptyValues()
    {
        $emptyData = [
            'newsfeed_id' => ''
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/delete-camp-newsfeed', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    public function testDeleteNewsFeedApiWithFalseData()
    {
        $emptyData = [
            'newsfeed_id' => '0'
        ];
        print sprintf("Test with id that dose not exist");
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/delete-camp-newsfeed', $emptyData);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * Check Api response code with correct data
     */
    public function testDeleteNewsFeedApiStatus()
    {
        $data = [
            'newsfeed_id' => 238
        ];
        print sprintf("\n post NewsFeed ", 200, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post(
            '/api/v3/delete-camp-newsfeed',
            $data
        );
        $this->assertEquals(200, $this->response->status());
    }

    /**
     * Check Api without user Auth
     */
    public function testDeleteNewsFeedwithoutUserAuth()
    {
        $data = [
            'newsfeed_id' => 299
        ];
        print sprintf("\n post NewsFeed ", 200, PHP_EOL);
        $this->post(
            '/api/v3/delete-camp-newsfeed',
            $data
        );
        $this->assertEquals(401, $this->response->status());
    }
}
