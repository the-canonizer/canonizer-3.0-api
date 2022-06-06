<?php

class GetCampBreadCrumbApiTest extends TestCase
{
 /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampBreadCrumbWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v3/get-camp-breadcrumb', []);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetCampBreadCrumbWithEmptyValues()
    {
        $emptyData = [
            'topic_num' => '',
            'camp_num' => '',
            'as_of' => "",
            'as_of_date' => ""
        ];
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v3/get-camp-breadcrumb', $emptyData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api response code with correct data
     */
    public function testGetCampBreadCrumbStatus()
    {
        $data = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "default"
        ];
        print sprintf("\n get camp bread crumb ", 200, PHP_EOL);
        $response = $this->call(
            'post',
            '/api/v3/get-camp-breadcrumb',
            $data
        );
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api with Invalid as_of filter value
     * validation
     */
    public function testGetCampBreadCrumbWithInvalidData()
    {
        $invalidData = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "xyz",
            'as_of_date' => "12-12-2022"
        ];

        print sprintf("Test with invalid values");
        $response = $this->call('POST', '/api/v3/get-camp-breadcrumb', $invalidData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetCampBreadCrumbWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "bydate"
        ];

        print sprintf("Test with invalid values");
        $response = $this->call('POST', '/api/v3/get-camp-breadcrumb', $invalidData);
        $this->assertEquals(400, $response->status());
    }

    /**
     * Check Api response structure
     */
    public function testGetCampBreadCrumbResponse()
    {
        $data = [
            'topic_num' => 95,
            'camp_num' => 5,
            'as_of' => "default"
        ];
        print sprintf("\n Test News Feed API Response ", 200, PHP_EOL);
        $this->call('POST', '/api/v3/get-camp-breadcrumb', $data);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                    'bread_crumb',
            ]
        ]);
    }
}
