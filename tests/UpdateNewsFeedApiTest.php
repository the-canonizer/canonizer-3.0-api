<?php

class UpdateNewsFeedApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testUpdateNewsFeedApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v3/update-camp-newsfeed', []);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testUpdateNewsFeedApiWithEmptyValues()
    {
        $emptyData = [
            "topic_num" => "",
            "camp_num" => "",
            "display_text" => "",
            "link" => "",
            "available_for_child" => ""
        ];
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v3/update-camp-newsfeed', $emptyData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testUpdateNewsFeedApiWithInvalidData()
    {
        $invalidData = [
            "topic_num" => 2,
            "camp_num" => 1,
            "display_text" => ["xyz"],
            "link" => ["facebook.com", "youtube.com"],
            "available_for_child" => [1, 1]
        ];
        print sprintf("Test with invalid values");
        $response = $this->call('POST', '/api/v3/update-camp-newsfeed', $invalidData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api response code with valid data
     */
    public function testUpdateNewsFeedApiStatus()
    {
       $data = [
            "topic_num" => 2,
            "camp_num" => 1,
            "display_text" => ["xyz", "abc"],
            "link" => ["facebook.com", "youtube.com"],
            "available_for_child" => [1, 1]
        ];
        print sprintf("\n Update NewsFeed ", 200, PHP_EOL);
        $response = $this->call(
            'post',
            '/api/v3/update-camp-newsfeed',
            $data
        );
        $this->assertEquals(200, $response->status());
    }
}
