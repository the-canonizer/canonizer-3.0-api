<?php

class CampStatementApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetStatementApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v3/get-camp-statement', []);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetStatementApiWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => '',
            'as_of' => "",
            'as_of_date' => ""
        ];
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v3/get-camp-statement', $emptyData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with Invalid as_of filter value
     * validation
     */
    public function testGetStatementApiWithInvalidData()
    {
        $invalidData = [
            'topic_num' => 279,
            'camp_num' => 4,
            'as_of' => "xyz"
        ];
        print sprintf("Test with invalid values");
        $response = $this->call('POST', '/api/v3/get-camp-statement', $invalidData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetCampStatementApiWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => 279,
            'camp_num' => 4,
            'as_of' => "bydate"
        ];

        print sprintf("Test with invalid values");
        $response = $this->call('POST', '/api/v3/get-camp-statement', $invalidData);
        $this->assertEquals(400, $response->status());
    }
    /**
     * Check Api response code with valid data
     */
    public function testGetStatementApiStatus()
    {
        $data = [
            'topic_num' => 279,
            'camp_num' => 4,
            'as_of' => "default"
        ];
        print sprintf("\n post Camp Statement ", 200, PHP_EOL);
        $response = $this->call(
            'post',
            '/api/v3/get-camp-statement',
            $data
        );
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetStatementApiResponse()
    {
        $data = [
            'topic_num' => 279,
            'camp_num' => 4,
            'as_of' => "default"
        ];
        print sprintf("\n Test  Camp Statement API Response ", 200, PHP_EOL);
        $this->call(
            'post',
            '/api/v3/get-camp-statement',
            $data
        );
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'id',
                    'value',
                    'note',
                    'go_live_time'
                ]
            ]
        ]);
    }
}
