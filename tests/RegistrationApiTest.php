<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class RegistrationApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

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
            "first_name" => "first_name",
            "last_name" => "last_name",
            "middle_name" => "middle_name",
            "email" => "email@email.com",
            "phone_number" => "8765432123",
            "country_code" => "country_code",
            "password" => "Test@123",
            "password_confirmation" => "Test@123",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }


    public function testRegisterEmptyParams()
    {
        print sprintf("Invalid details submitted %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '/api/v3/register', []);
        $this->assertEquals(400, $response->status());       
    }

    public function testWhenIncorrectCurrentPassword(){
        print sprintf("Same New And Current Password %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '/api/v3/register', ['password'=>'Test#123','password_confirmation'=>'Test#123']);
        $this->assertEquals(400, $response->status());
    }

    public function testSuccessfulRegistration()
    {

        print sprintf("Valid details submitted %d %s", 302,PHP_EOL);

        $parameters = [
            "first_name" => "first_name",
            "last_name" => "last_name",
            "middle_name" => "middle_name",
            "email" => "email@email.com",
            "phone_number" => "8765432123",
            "country_code" => "country_code",
            "password" => "Test@123",
            "password_confirmation" => "Test@123",
            "otp" => "123456",
        ];

        $this->post("api/v3/register", $parameters, []);
        $this->seeStatusCode(200);
        $this->seeJsonStructure(
            [
                "status_code" => 200,
                "message" => "Otp sent successfully on your registered Email Id",
                "error" => null,
                "data" => null
            ]
        );
    }
}
