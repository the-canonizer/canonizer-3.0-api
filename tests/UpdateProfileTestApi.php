<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class UpdateProfiletestApi extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testValidateFiled()
    {
        print sprintf("\n Validation working %d %s", 200 ,PHP_EOL);
        $rules = [
            'first_name' => 'required|regex:/^[a-zA-Z ]*$/|string|max:100',
            'last_name' => 'required|regex:/^[a-zA-Z ]*$/|string|max:100',
            'middle_name' => 'nullable|regex:/^[a-zA-Z ]*$/|string|max:100',
            'city' => 'nullable|regex:/^[a-zA-Z ]*$/|string|max:100',
            'state' => 'nullable|regex:/^[a-zA-Z ]*$/|string|max:100',
            'country' => 'nullable|regex:/^[a-zA-Z ]*$/|string|max:100',
            'postal_code' => 'nullable|regex:/^[a-zA-Z ]*$/|string|max:100',
            'phone_number' => 'nullable|digits:10',
        ];
        
        $data = [
            "first_name" => "first name",
            "last_name" => "last name",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testUnauthorizedUserCanNotUpdate(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 500,PHP_EOL);
        $response = $this->call('POST', '/api/v3/updateprofile', []);
        $this->assertEquals(500, $response->status()); 
    }


    public function testUpdateWithInvalidData()
    {
        print sprintf(" \n Invalid details submitted %d %s", 400,PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)
        ->post('/api/v3/updateprofile',[]);    
        $this->assertEquals(400, $this->response->status());
    }

    public function testUpdateWithValidaData(){
        print sprintf(" \n Profile updated wit valid data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)
        ->post('/api/v3/updateprofile',['first_name'=>$user->first_name,'last_name'=>$user->last_name]);
        $this->assertEquals(200, $this->response->status());
    }

  /*  public function testSuccessfulRegistration()
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
    }*/
}
