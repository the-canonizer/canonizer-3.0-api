<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class SocialApiTest extends TestCase
{

    use WithoutMiddleware;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testSocialLoginValidateFiled()
    {
        $rules = [
            'provider' => 'required'
        ];
        $data = [
            'provider' => 'google'
        ];
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testSocialLoginWithInvalidData()
    {
        print sprintf(" \n Invalid Social Login provider submitted %d %s", 400, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            'provider' => ''
        ];
        $response = $this->actingAs($user)->post('/api/v3/user/social/login', $parameters);
        $response->assertStatus(400);
    }

    public function testSocialLoginWithValidData()
    {
        print sprintf(" \n Valid Social Login provider submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            'provider' => 'google'
        ];
        $response = $this->actingAs($user)->post('/api/v3/user/social/login', $parameters);
        $response->assertStatus(200);
    }

    public function testSociaCallbackValidateFiled()
    {
        $rules = [
            'client_id' => 'required',
            'client_secret' => 'required',
            'provider' => 'required',
            'code' => 'required'
        ];
        $data = [
            "client_id" => "4",
            "client_secret" => "vzPs1YN0KOqImwj6TFdFt6LMekguxE1EX5xoh4A4",
            'provider' => 'google',
            'code' => 'goovzPs1YN0KOqImwj6TFdFt6LMekguxE1EX5xoh4A4gle'
        ];
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testSocialCallbackWithInvalidData()
    {
        print sprintf(" \n Invalid Social Login provider submitted %d %s", 400, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            "client_id" => "",
            "client_secret" => "",
            'provider' => '',
            'code' => ''
        ];
        $response = $this->actingAs($user)->post('/api/v3/user/social/callback', $parameters);
        $response->assertStatus(400);
    }

    public function testSocialCallbackWithValidData()
    {
        print sprintf(" \n Valid Social Login provider submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            "client_id" => "4",
            "client_secret" => "vzPs1YN0KOqImwj6TFdFt6LMekguxE1EX5xoh4A4",
            'provider' => 'google',
            'code' => 'goovzPs1YN0KOqImwj6TFdFt6LMekguxE1EX5xoh4A4gle'
        ];
        $response = $this->call('POST', '/api/v3/user/social/callback', $parameters);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                   
            ]
        ]);
    }

    public function testSocialSocialLinkWithValidData()
    {
        print sprintf(" \n Valid Social Login provider submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            "client_id" => "4",
            "client_secret" => "vzPs1YN0KOqImwj6TFdFt6LMekguxE1EX5xoh4A4",
            'provider' => 'google',
            'code' => 'goovzPs1YN0KOqImwj6TFdFt6LMekguxE1EX5xoh4A4gle'
        ];

        $response = $this->call('POST', '/api/v3/user/social/social-link', $parameters);

        // dd($response);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                   
            ]
        ]);
    }

    public function testGetSocialUserListInvalidData(){
        print sprintf("\n Get Social User List Invalid Data %d %s",400, PHP_EOL);
        $response = $this->call('GET', '/api/v3/user/social/list');
        $response->assertStatus(400); 
    }

    public function testGetSocialUserListValidData(){
        print sprintf(" \n  Get Social User List Valid Data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $this->actingAs($user)
        ->get('/api/v3/user/social/list', []);

        $response->assertStatus(200);
    }

    public function testGetSocialUserListDeleteValidData(){
        print sprintf(" \n  Get Social User List Valid Data %d %s", 200,PHP_EOL);
        $response = $this->call('DELETE', '/api/v3/user/social/delete/2');
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data'
        ]);
        
    }

    public function testSocialDeactivateUserWithInvalidData()
    {
        print sprintf(" \n Invalid Social Login provider submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            'user_id' => ''
        ];
        $response = $this->actingAs($user)->post('/api/v3/user/deactivate', $parameters);
        $response->assertStatus(400);
    }

    public function testSocialDeactivateUserWithValidData()
    {
        print sprintf(" \n Valid Social Login provider submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make([
            'id'=> trans('testSample.user_ids.normal_user.user_2.id'),
        ]);
        $parameters = [
            'user_id' => trans('testSample.user_ids.normal_user.user_2.id'),
        ];
        $response = $this->actingAs($user)->post('/api/v3/user/deactivate', $parameters);
        $response->assertStatus(200);
    }

}
