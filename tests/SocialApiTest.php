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
        $_res = $this->actingAs($user)->post('/api/v3/user/social/login', $parameters);
        $_res->assertStatus(400);
    }

    public function testSocialLoginWithValidData()
    {
        print sprintf(" \n Valid Social Login provider submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            'provider' => 'google'
        ];
        $_res = $this->actingAs($user)->post('/api/v3/user/social/login', $parameters);
        $_res->assertStatus(200);
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
        $_res = $this->actingAs($user)->post('/api/v3/user/social/callback', $parameters);
        $_res->assertStatus(400);
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
        $_res = $this->call('POST', '/api/v3/user/social/callback', $parameters);
        $_res->assertJsonStructure([
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

        $_res = $this->call('POST', '/api/v3/user/social/social-link', $parameters);

        // dd($_res);
        $_res->assertJsonStructure([
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
        $_res->assertStatus(400); 
    }

    public function testGetSocialUserListValidData(){
        print sprintf(" \n  Get Social User List Valid Data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $this->actingAs($user)
        ->get('/api/v3/user/social/list', []);

        $_res->assertStatus(200);
    }

    public function testGetSocialUserListDeleteValidData(){
        print sprintf(" \n  Get Social User List Valid Data %d %s", 200,PHP_EOL);
        $_res = $this->call('DELETE', '/api/v3/user/social/delete/2');
        $_res->assertJsonStructure([
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
        $_res = $this->actingAs($user)->post('/api/v3/user/deactivate', $parameters);
        $_res->assertStatus(400);
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
        $_res = $this->actingAs($user)->post('/api/v3/user/deactivate', $parameters);
        $_res->assertStatus(200);
    }

}
