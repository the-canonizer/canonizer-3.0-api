<?php

namespace Tests;

use App\Models\User;

class GetCampBreadCrumbApiTest extends TestCase
{
    protected $user;
    protected $nickname;
    protected $topic;
    protected $camp;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->nickname = \App\Models\Nickname::factory()->create(['user_id' => $this->user->id]);
        $this->topic = \App\Models\Topic::factory()->create(['submitter_nick_id' => $this->nickname->id]);
        $this->camp = \App\Models\Camp::factory()->create(['topic_num' => $this->topic->topic_num, 'camp_num' => 1, 'submitter_nick_id' => $this->nickname->id]);
    }

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetCampBreadCrumbWithEmptyFormData()
    {
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-breadcrumb',[],$header);
        $response->assertStatus(400);
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
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-breadcrumb', $emptyData,$header);
        $response->assertStatus(400);
    }

    /**
     * Check Api response code with correct data
     */                                                                
    public function testGetCampBreadCrumbStatus()
    {
        $data = [
            'topic_num' => $this->topic->topic_num,
            'camp_num' => $this->camp->camp_num,
            'as_of' => "default"
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-breadcrumb', $data,$header);
        $response->assertStatus(200);
    }

    /**
     * Check Api with Invalid as_of filter value
     * validation
     */
    public function testGetCampBreadCrumbWithInvalidData()
    {
        $invalidData = [
            'topic_num' => $this->topic->topic_num,
            'camp_num' => $this->camp->camp_num,
            'as_of' => "xyz",
            'as_of_date' => "12-12-2022"
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-breadcrumb', $invalidData,$header);
        $response->assertStatus(400);
    }

    /**
     * Check Api with as_of filter value bydate without as_of_date
     * validation
     */
    public function testGetCampBreadCrumbWithoutFilterDate()
    {
        $invalidData = [
            'topic_num' => $this->topic->topic_num,
            'camp_num' => $this->camp->camp_num,
            'as_of' => "bydate"
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-breadcrumb', $invalidData,$header);
        $response->assertStatus(400);
    }

    /**
     * Check Api response structure
     */
    public function testGetCampBreadCrumbResponse()
    {
        $data = [
            'topic_num' => $this->topic->topic_num,
            'camp_num' => $this->camp->camp_num,
            'as_of' => "default"
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-breadcrumb', $data,$header);
        $response->assertStatus(200);
    }

    public function testIfNoBreadcrumbFound()
    {
        $data = [
            'topic_num' => 1231233,
            'camp_num' => 5,
            "as_of" => "default",
            "as_of_date" => 1696854130.086
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-breadcrumb', $data, $header);
        $response->assertStatus(404);
    }

    public function testToSeeApiStructure()
    {
        $data = [
            'topic_num' => $this->topic->topic_num,
            'camp_num' => $this->camp->camp_num,
            "as_of" => "default",
            "as_of_date" => 1696854130.086
        ];
        $token = $this->user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($this->user)->post('/api/v3/get-camp-breadcrumb', $data, $header);
        $response->assertJsonStructure([
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
