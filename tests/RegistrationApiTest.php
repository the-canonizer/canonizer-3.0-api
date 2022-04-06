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
            "first_name" => "saurabh",
             "last_name" => "singh",
            "middle_name" => "kumar",
            "email" => "saurabh.singh55@iffort.com",
            "phone_number" => "8765432123",
            "country_code" => "+91",
            "password" => "Test@123",
            "password_confirmation" => "Test@123", 
        ];
        
        $v = $this->app['validator']->make($data, $rules);
       // dd($v);
        $this->assertTrue($v->passes());
    }


    public function testRegisterEmptyParams()
    {
        print sprintf("Invalid Register details submitted %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '/api/v3/register', []);
        $this->assertEquals(400, $response->status());       
    }

    public function testWhenIncorrectRegisterCurrentPassword(){
        print sprintf("Same Register New And Current Password %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '/api/v3/register', ['password'=>'Test#123','password_confirmation'=>'Test#123']);
        $this->assertEquals(400, $response->status());
    }

    public function testRegisterWithInvalidData()
    {
        print sprintf(" \n Invalid Register details submitted %d %s", 400,PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)
        ->post('/api/v3/register',[]);    
        $this->assertEquals(400, $this->response->status());
    }


    public function testRegisterWithValidaData(){
        
        print sprintf(" \n Register with valid data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
 
        $parameters = [
            "first_name" => "saurabh",
            "last_name" => "singh",
            "middle_name" => "kumar",
            "email" => "saurabh.singh55@iffort.com",
            "phone_number" => "8765432123",
            "country_code" => "+91",
            "password" => "Test@123",
            "password_confirmation" => "Test@123",
            "otp" => "123456"
        ];
        $this->actingAs($user)
        ->post('/api/v3/register',$parameters);
        $this->assertEquals(200, $this->response->status());
    }

}
