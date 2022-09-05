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
        $response = $this->call('POST', '/api/v3/forgotpassword/update', []);
        $this->assertEquals(400, $response->status());
    }

    public function testForgotPasswordUnauthorizedUserCanNotUpdate()
    {
        print sprintf("\n Unauthorized ForgotPassword update User can not  request this api %d %s", 500, PHP_EOL);
        $response = $this->call('POST', '/api/v3/forgotpassword/update', []);
        $this->assertEquals(400, $response->status());
    }

    public function testForgotPasswordUpdateValidateFiled()
    {
        $rules = [
            'username' => 'required',
            "new_password" => ['required', 'regex:/^(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{8,}$/', 'different:current_password'],
            "confirm_password" => 'required|same:new_password'
        ];

        $data = [
            "username" => "saurabh.singh55@iffort.com",
            "new_password" => "Test@1234",
            "confirm_password" => "Test@1234",
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testForgotPasswordUpdateWithInvalidData()
    {
        print sprintf(" \n Invalid Forgot Password Update details submitted %d %s", 400, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)
            ->post('/api/v3/forgotpassword/update', []);
        $this->assertEquals(400, $this->response->status());
    }


    public function testForgotPasswordUpdateWithValidaData()
    {
        print sprintf(" \n Forgot Password updated wit valid data %d %s", 200, PHP_EOL);
        $user = User::factory()->make();

        $data = [
            "username" => "saurabh.singh@iffort.com",
            "new_password" => "Test@1234",
            "confirm_password" => "Test@1234",
        ];

        $this->actingAs($user)
            ->post('/api/v3/forgotpassword/update',  $data);
        $this->assertEquals(200, $this->response->status());
    }
}
