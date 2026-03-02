<?php

namespace Tests;

use App\Models\User;

class LoginAsAdminApiTest extends TestCase
{

    /**
     * Check Api with empty form data
     * validation
     */
    public function testLoginAsAdminApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/login-as-user', []);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with empty data
     * validation
     */
    public function testLoginAsAdminApiWithEmptyValues()
    {
        $emptyData = [
            "id" => ""
        ];
        print sprintf("Test with empty values");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/login-as-user', $emptyData);
        $_res->assertStatus(400);
    }

    /**
     * Check Api with invalid data
     * validation
     */
    public function testLoginAsAdminApiWithInvalidData()
    {
        $invalidData = [
            "id" => "abc",
        ];
        print sprintf("Test with invalid values");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/login-as-user', $invalidData);
        $_res->assertStatus(400);
    }

    /**
     * Check Api without auth
     * validation
     */
    public function testLoginAsAdminApiWithoutUserAuth()
    {
        print sprintf("Test with empty form data");
        $_res = $this->post('/api/v3/login-as-user', []);
        $_res->assertStatus(401);
    }

    /**
     * Check Api with normal user auth
     * validation
     */
    public function testLoginAsAdminApiWithNormalUserAuth()
    {
        print sprintf("Test with normal user auth");
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_4.id')
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/login-as-user', []);
        $_res->assertStatus(401);
    }

    /**
     * Check Api response structure
     */
    public function testLoginAsAdminApiResponse()
    {
        $data = [
            "id" => trans('testSample.user_ids.normal_user.user_4.id'),
        ];
        print sprintf("\n Test Login As User API Response ", 200, PHP_EOL);
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.admin_user.admin_1'),
            'type' => 'admin'
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/login-as-user', $data);
        
        $_res->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                'access_token',
                'user' =>  [
                    'id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'mobile_verified',
                    'birthday',
                    'default_algo',
                    'private_flags',
                    'join_time',
                    'is_admin'
                ]

            ]
        ]);
    }
}
