<?php

class GetNewsFeedApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetNewsFeedApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v3/get-camp-newsfeed', []);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetNewsFeedApiWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => ''
        ];
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v3/get-camp-newsfeed', $emptyData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api response code with valid data
     */
    public function testGetNewsFeedApiStatus()
    {
        $data = [
            'topic_num' => 2,
            'camp_num' => 1
        ];    
        print sprintf("\n post NewsFeed ", 200, PHP_EOL);
        $response = $this->call(
            'post',
            '/api/v3/get-camp-newsfeed',
            $data
        );
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetNewsFeedApiResponse()
    {
        $data = [
            'topic_num' => 2,
            'camp_num' => 1
        ]; 
        print sprintf("\n Test News Feed API Response ", 200, PHP_EOL);
        $this->call('POST', '/api/v3/get-camp-newsfeed', $data);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'id',
                    'display_text',
                    'link',
                    'available_for_child'
                ]
            ]
        ]);
    }
}
