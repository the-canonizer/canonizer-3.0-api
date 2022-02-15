<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class PasswordApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Invalid data Test
     * check validation in that case
     * */

    public function testGuestuserCanNotAccessApi(){
        print sprintf("Invalid details submitted %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '/api/v3/changepassword', []);
        $this->assertEquals(401, $response->status());       
    }

    public function testPasswordApiWithInvalidData()
    {
        print sprintf("Invalid details submitted %d %s", 302,PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)
        ->post('/api/v3/changepassword',[]);    
        $this->assertEquals(400, $this->response->status()); 
    }

    public function testWhenIncorrectCurrentPassword(){
        print sprintf("Incorrect Current Password Given %d %s", 302,PHP_EOL);
        $user = User::factory()->make();
        $parameter = [
            'current_password'=>'test',
            'new_password'=>'Test@123',
            'confirm_password'=>'Test@123'

        ];

        $this->actingAs($user)->post('/api/v3/changepassword', $parameter); 
        $this->assertEquals(400, $this->response->status()); 
    }

    public function testWhenSameNewAndCurrentPassword(){
        print sprintf("Same New And Current Password %d %s", 302,PHP_EOL);
        $parameter = [
            'current_password'=>'password',
            'new_password'=>'password',
            'confirm_password'=>'password'

        ];

        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/changepassword', $parameter); 
        $this->assertEquals(400, $this->response->status());

    }

    public function testPasswordApiWithValidData()
    {
        print sprintf("Valid details submitted %d %s", 302,PHP_EOL);
        $parameter = [
            'current_password'=>'password',
            'new_password'=>'Test@123',
            'confirm_password'=>'Test@123'

        ];
        
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/changepassword', $parameter); 
        $this->assertEquals(200, $this->response->status());
       
    }
}
