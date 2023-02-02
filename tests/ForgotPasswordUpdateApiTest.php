<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class ForgotPasswordUpdateApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Invalid data Test
     * check validation in that case
     * */
    public function testForgotPasswordApiWithInvalidData()
    {
        print sprintf("Invalid Forgot Password update details submitted %d %s", 302, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
         $this->actingAs($user)
        ->post('/api/v3/forgot-password/update',  [],$header);
        // dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    public function testForgotPasswordUnauthorizedUserCanNotUpdate()
    {
        print sprintf("\n Unauthorized ForgotPassword update User can not  request this api %d %s", 500, PHP_EOL);
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)
        ->post('/api/v3/forgot-password/update',  [],$header);
        // dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }

    public function testForgotPasswordUpdateValidateFiled()
    {
        $rules = [
            'username' => 'required',
            "new_password" => ['required', 'regex:/^(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{8,}$/', 'different:current_password'],
            "confirm_password" => 'required|same:new_password'
        ];

        $data = [
            "username" =>  trans('testSample.user_ids.normal_user.user_2.email'),
            "new_password" => trans('testSample.user_ids.normal_user.user_2.password'),
            "confirm_password" =>  trans('testSample.user_ids.normal_user.user_2.password'),
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testForgotPasswordUpdateWithInvalidData()
    {
        print sprintf(" \n Invalid Forgot Password Update details submitted %d %s", 400, PHP_EOL);

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $user = User::factory()->make();
        $this->actingAs($user)
            ->post('/api/v3/forgot-password/update', [],$header);
        $this->assertEquals(400, $this->response->status());
    }


    public function testForgotPasswordUpdateWithValidaData()
    {
        print sprintf(" \n Forgot Password updated wit valid data %d %s", 200, PHP_EOL);
        $user = User::factory()->make();

        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $data = [
            "username" => trans('testSample.user_ids.normal_user.user_2.email'),
            "new_password" => trans('testSample.user_ids.normal_user.user_2.password'),
            "confirm_password" => trans('testSample.user_ids.normal_user.user_2.password'),
        ];

        $this->actingAs($user)
            ->post('/api/v3/forgot-password/update',  $data,$header);
        $this->assertEquals(200, $this->response->status());
    }
}
