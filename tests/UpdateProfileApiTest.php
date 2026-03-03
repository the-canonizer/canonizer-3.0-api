<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class UpdateProfileApiTest extends TestCase
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

    public function testUnauthorizedUserCannotUpdate(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 500,PHP_EOL);
        $response = $this->json('POST', '/api/v3/update-profile', []);
        $response->assertStatus(401);
    }


    public function testUpdateWithInvalidData()
    {
        print sprintf(" \n Invalid details submitted %d %s", 400,PHP_EOL);
        $user = User::factory()->make();
        $response = $this->actingAs($user)
        ->json('POST', '/api/v3/update-profile',[]);
        $response->assertStatus(400);
    }

    public function testUpdateWithValidaData(){
        print sprintf(" \n Profile updated wit valid data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $response = $this->actingAs($user)
        ->json('POST', '/api/v3/update-profile',['first_name'=>$user->first_name,'last_name'=>$user->last_name]);
        $response->assertStatus(200);
    }
}
