<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RegistrationApiTest extends TestCase
{
    use DatabaseTransactions;

    public function testValidateFiled()
    {
        $rules = [
            'first_name' => 'required|regex:/^[a-zA-Z ]*$/|string|max:100',
            'last_name' => 'required|regex:/^[a-zA-Z ]*$/|string|max:100',
            'middle_name' => 'nullable|regex:/^[a-zA-Z ]*$/|max:100',
            'email' => 'required|string|email|max:225|unique:person',
            'password' => ['required','regex:/^(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{8,}$/'],
            'password_confirmation' => 'required|same:password',
            'phone_number' => 'required|unique:person',
            'country_code' => 'required', 
        ];
        
        $data = [
            "first_name" => "John",
            "last_name" => "Doe",
            "middle_name" => "M",
            "email" => "john.doe.".rand(100,999)."@example.com",
            "phone_number" =>  "1234567".rand(100,999),
            "country_code" => "US",
            "password" => "Test#1234",
            "password_confirmation" => "Test#1234",
        ];
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }


    public function testRegisterEmptyParams()
    {
        print sprintf("Invalid Register details submitted %d %s", 400, PHP_EOL);
        $response = $this->postJson('/api/v3/register', []);
        $response->assertStatus(400);      
    }

    public function testRegisterWithInvalidData()
    {
        print sprintf(" \n Invalid Register details submitted %d %s", 400, PHP_EOL);
        $response = $this->postJson('/api/v3/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            // missing email, password, etc.
        ]);
        $response->assertStatus(400);      
    }


    public function testRegisterWithValidData()
    {
        print sprintf(" \n Register with valid data %d %s", 200, PHP_EOL);
        
        $email = "testuser.".rand(100,9999)."@example.com";
        $parameters = [
            "first_name" => "Test",
            "last_name" => "User",
            "email" => $email,
            "business_email" => $email,
            "phone_number" =>  "1234567890",
            "country_code" => "1",
            "password" => "Test#1234",
            "password_confirmation" => "Test#1234",
            "captcha_token" => "test_captcha_token"
        ];
        
        $response = $this->postJson('/api/v3/register', $parameters);
        $response->assertStatus(200);      
    }
}
