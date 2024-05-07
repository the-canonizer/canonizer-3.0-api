<?php

use App\Models\User;

class GetCampBreadCrumbApiTest extends TestCase
{
 /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampBreadCrumbWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-breadcrumb',[],$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
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
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-breadcrumb', $emptyData,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
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
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-breadcrumb', $data,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
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

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-breadcrumb', $invalidData,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
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

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-breadcrumb', $invalidData,$header);
        //  dd($this->response);
        $this->assertEquals(400, $this->response->status());
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

        print sprintf("\n Test Breadcrumb API Response ", 200, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/get-camp-breadcrumb', $data,$header);
        $this->assertEquals(200, $this->response->status());
    }

    public function testIfNoBreadcrumbFound()
    {
        $data = [
            'topic_num' => 1231233,
            'camp_num' => 5,
            "as_of" => "default",
            "as_of_date" => 1696854130.086
        ];

        print sprintf("\n Test BreadCrumb not found ");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-breadcrumb', $data, $header);
        $this->assertEquals(404, $this->response->status());
    }

    public function testToSeeApiStructure()
    {
        $data = [
            'topic_num' => 95,
            'camp_num' => 5,
            "as_of" => "default",
            "as_of_date" => 1696854130.086
        ];

        print sprintf("\n Test for correct api structure ");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $this->actingAs($user)->post('/api/v3/get-camp-breadcrumb', $data, $header);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                'bread_crumb' => [],
                "flag",
                "subscription_id",
                "subscribed_camp_name",
                "topic_name",
            ]
        ]);
    }
}
