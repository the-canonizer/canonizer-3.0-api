<?php


class GetCampRecordApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampRecordApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v3/get-camp-record', []);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetCampRecordApiWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => '',
            'as_of' => "",
            'as_of_date' => ""
        ];
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v3/get-camp-record', $emptyData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api response code with correct data
     */
    public function testGetCampRecordApiStatus()
    {
        $data = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "default"
        ];
        print sprintf("\n get camp record ", 200, PHP_EOL);
        $response = $this->call(
            'post',
            '/api/v3/get-camp-record',
            $data
        );
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api with Invalid as_of filter value
     * validation
     */
    public function testGetCampRecordApiWithInvalidData()
    {
        $invalidData = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "xyz",
            'as_of_date' => "12-12-2022"
        ];

        print sprintf("Test with invalid values");
        $response = $this->call('POST', '/api/v3/get-camp-record', $invalidData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetCampRecordApiWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "bydate"
        ];

        print sprintf("Test with invalid values");
        $response = $this->call('POST', '/api/v3/get-camp-record', $invalidData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetCampRecordApiResponse()
    {
        $data = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "default"
        ];
        print sprintf("\n Test News Feed API Response ", 200, PHP_EOL);
        $this->call('POST', '/api/v3/get-camp-record', $data);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                    'topic_num',
                    'camp_num',
                    'key_words',
                    'camp_about_url',
                    'nick_name',
                    'subscriptionCampName',
                    'subscriptionId',
                    'flag',
                    'parent_camp_name',
                    'parentCamps',
                    'camp_name'
            ]
        ]);
    }
}
