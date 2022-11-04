<?php

class GetCampActivityLogApiTest extends TestCase  
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampActivityLogApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v3/get-camp-activity-log', []);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetCampActivityLogApiWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => ''
        ];
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v3/get-camp-activity-log', $emptyData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api response code with valid data
     */
    public function testGetCampActivityLogApiStatus()
    {
        $data = [
            'topic_num' => 2,
            'camp_num' => 1
        ];    
        print sprintf("\n post camp activity log", 200, PHP_EOL);
        $response = $this->call(
            'post',
            '/api/v3/get-camp-activity-log',
            $data
        );
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetCampActivityLogApiResponse()
    {
        $data = [
            'topic_num' => 1,
            'camp_num' => 1
        ]; 
        print sprintf("\n Test camp activity log API response ", 200, PHP_EOL);
        $this->call('POST', '/api/v3/get-camp-activity-log', $data);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }
}
