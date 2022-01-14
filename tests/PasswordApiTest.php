<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\user;

class PasswordApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Invalid data Test
     * check validation in that case
     * @return void
     */
    public function testPasswordApiWithInvalidData()
    {
        print sprintf("Invalid details submitted %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '/api/v1/changepassword', []);
        $this->assertEquals(400, $response->status());       
    }

    public function testWhenIncorrectCurrentPassword(){
        print sprintf("Incorrect Current Password Given %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '//api/v1/changepassword', ['current_password'=>'reena','new_password'=>'Test#123','confirm_password'=>'Test#123']);
        $this->assertEquals(400, $response->status());
    }

    public function testWhenSameNewAndCurrentPassword(){
        print sprintf("Same New And Current Password %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '//api/v1/changepassword', ['current_password'=>'reena','new_password'=>'reena','confirm_password'=>'reena']);
        $this->assertEquals(400, $response->status());
    }

    public function testPasswordApiWithValidData()
    {
        print sprintf("Valid details submitted %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '//api/v1/changepassword', ['current_password'=>'Reenanalwa#237','new_password'=>'Nalwa#237','confirm_password'=>'Nalwa#237']);
        $this->assertEquals(200, $response->status());
       
    }
}
