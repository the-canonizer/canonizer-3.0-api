<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialApiTest extends TestCase
{

    use DatabaseTransactions;

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
        $this->actingAs($user)->post('/api/v3/user/social/login', $parameters);
        $this->assertEquals(400, $this->response->status());
    }

    public function testSocialLoginWithValidData()
    {
        print sprintf(" \n Valid Social Login provider submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            'provider' => 'google'
        ];
        $this->actingAs($user)->post('/api/v3/user/social/login', $parameters);
        $this->assertEquals(200, $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/user/social/callback', $parameters);
        $this->assertEquals(400, $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/user/social/callback', $parameters);
        $this->assertEquals(200, $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/user/social/callback', $parameters);
        $this->assertEquals(200, $this->response->status());
    }

    public function testGetSocialUserListInvalidData(){
        print sprintf("\n Get Social User List Invalid Data %d %s",401, PHP_EOL);
        $response = $this->call('GET', '/api/v3/user/social/list');
        $this->assertEquals(401, $response->status()); 
    }

    public function testGetSocialUserListValidData(){
        print sprintf(" \n  Get Social User List Valid Data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $this->actingAs($user)
        ->get('/api/v3/user/social/list', []);

        $this->assertEquals(200, $this->response->status());
    }

    /* public function testGetSocialUserListDeleteValidData(){
        print sprintf(" \n  Get Social User List Valid Data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->user_id = 1;
        #$this->actingAs($user)
       # ->delete('/api/v3/user/social/delete/{id}', []);
       dd($this->response); die;
       $this->json('DELETE', '/api/v3/user/social/delete/' . $user->user_id, [])->assertEquals(200, $this->response->status());
        #$this->assertEquals(200, $this->response->status());
    } */

    public function testSocialDeactivateUserWithInvalidData()
    {
        print sprintf(" \n Invalid Social Login provider submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            'user_id' => ''
        ];
        $this->actingAs($user)->post('/api/v3/user/deactivate', $parameters);
        $this->assertEquals(400, $this->response->status());
    }

    public function testSocialDeactivateUserWithValidData()
    {
        print sprintf(" \n Valid Social Login provider submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            'user_id' => '2'
        ];
        $this->actingAs($user)->post('/api/v3/user/deactivate', $parameters);
        $this->assertEquals(200, $this->response->status());
    }

}
