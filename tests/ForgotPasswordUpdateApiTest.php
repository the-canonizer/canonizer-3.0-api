<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\user;

class ForgotPasswordUpdateApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Invalid data Test
     * check validation in that case
     * */
    public function testForgotPasswordApiWithInvalidData()
    {
        print sprintf("Invalid details submitted %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '/api/v1/forgotpassword/update', []);
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
            "username" => "email@email.com",
            "new_password" => "987654",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testForgotPasswordApiWithValidData()
    {
        print sprintf("Valid details submitted %d %s", 302,PHP_EOL);

        $parameters = [
            "username" => "email@email.com",
            "new_password" => "987654",
        ];

        $this->post("api/v3/forgotpassword/update", $parameters, []);
        $this->seeStatusCode(200);
        $this->seeJsonStructure(
            [
                "status_code" => 200,
                "message" => "Password changed successfully",
                "error" => null,
                "data" => null
            ]
        );
       
    }
}
