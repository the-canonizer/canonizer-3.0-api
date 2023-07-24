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
            "first_name" => trans('testSample.user_ids.normal_user.user_2.first_name'),
            "last_name" => trans('testSample.user_ids.normal_user.user_2.last_name'),
            "middle_name" => trans('testSample.user_ids.normal_user.user_2.middle_name'),
            "email" => rand(100,999).trans('testSample.user_ids.normal_user.user_2.email'),
            "phone_number" =>  trans('testSample.user_ids.normal_user.user_2.phone_number'),
            "country_code" => trans('testSample.user_ids.normal_user.user_2.country_code'),
            "password" => trans('testSample.user_ids.normal_user.user_2.password'),
            "password_confirmation" => trans('testSample.user_ids.normal_user.user_2.password'),
        ];
        // dd($data);
        $v = $this->app['validator']->make($data, $rules);
       // dd($v);
        $this->assertTrue($v->passes());
    }


    public function testRegisterEmptyParams()
    {
        print sprintf("Invalid Register details submitted %d %s", 302,PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/register',[],$header);
        $this->assertEquals(400, $this->response->status());      
    }

    public function testWhenIncorrectRegisterCurrentPassword(){
        print sprintf("Same Register New And Current Password %d %s", 302,PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/register',['password'=>'Test#123','password_confirmation'=>'Test#123'],$header);
        $this->assertEquals(400, $this->response->status());
    }

    public function testRegisterWithInvalidData()
    {
        print sprintf(" \n Invalid Register details submitted %d %s", 400,PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/register',[],$header);
        $this->assertEquals(400, $this->response->status());      
    }


    public function testRegisterWithValidData(){
        
        print sprintf(" \n Register with valid data %d %s", 200,PHP_EOL);
        $user = User::factory()->make([
            "first_name" => trans('testSample.user_ids.normal_user.user_2.first_name'),
            "last_name" => trans('testSample.user_ids.normal_user.user_2.last_name'),
            "middle_name" => trans('testSample.user_ids.normal_user.user_2.middle_name'),
            "email" => trans('testSample.user_ids.normal_user.user_2.email'),
            "phone_number" =>  trans('testSample.user_ids.normal_user.user_2.phone_number'),
            "country_code" => trans('testSample.user_ids.normal_user.user_2.country_code'),
            "password" => trans('testSample.user_ids.normal_user.user_2.password'),
            "password_confirmation" => trans('testSample.user_ids.normal_user.user_2.password'),
            "otp" =>  trans('testSample.user_ids.normal_user.user_2.otp'),
        ]);
 
        $parameters = [
            "first_name" => trans('testSample.user_ids.normal_user.user_2.first_name'),
            "last_name" => trans('testSample.user_ids.normal_user.user_2.last_name'),
            "middle_name" => trans('testSample.user_ids.normal_user.user_2.middle_name'),
            "email" => rand(100,999).trans('testSample.user_ids.normal_user.user_2.email'),
            "phone_number" =>  trans('testSample.user_ids.normal_user.user_2.phone_number'),
            "country_code" => trans('testSample.user_ids.normal_user.user_2.country_code'),
            "password" => trans('testSample.user_ids.normal_user.user_2.password'),
            "password_confirmation" => trans('testSample.user_ids.normal_user.user_2.password'),
            "otp" =>  trans('testSample.user_ids.normal_user.user_2.otp'),
        ];
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/register',$parameters,$header);
        dd($this->response);
        $this->assertEquals(200, $this->response->status());      
    }

}
