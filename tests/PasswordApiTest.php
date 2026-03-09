<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;

class PasswordApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Invalid data Test
     * check validation in that case
     * */

    public function testGuestuserCannotAccessApi(){
        print sprintf("Invalid details submitted %d %s", 401, PHP_EOL);
        $response = $this->postJson('/api/v3/change-password', []);
        $response->assertStatus(401);
    }

    public function testPasswordApiWithInvalidData()
    {
        print sprintf("Invalid details submitted %d %s", 400, PHP_EOL);
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/api/v3/change-password', []);
        $response->assertStatus(400);
    }

    public function testWhenIncorrectCurrentPassword(){
        print sprintf("Incorrect Current Password Given %d %s", 400, PHP_EOL);
        $user = User::factory()->create([
            'password' => Hash::make('correct_password')
        ]);
        $parameter = [
            'current_password' => 'wrong_password',
            'new_password' => 'Test@123',
            'confirm_password' => 'Test@123'
        ];

        $response = $this->actingAs($user)->postJson('/api/v3/change-password', $parameter);
        $response->assertStatus(400);
    }

    public function testWhenSameNewAndCurrentPassword(){
        print sprintf("Same New And Current Password %d %s", 400, PHP_EOL);
        $parameter = [
            'current_password' => 'password',
            'new_password' => 'password',
            'confirm_password' => 'password'
        ];

        $user = User::factory()->create([
            'password' => Hash::make('password')
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/change-password', $parameter);
        $response->assertStatus(400);
    }

    public function testPasswordApiWithValidData()
    {
        print sprintf("Valid details submitted %d %s", 200, PHP_EOL);
        $parameter = [
            'current_password' => 'password',
            'new_password' => 'Test@123',
            'confirm_password' => 'Test@123'
        ];

        $user = User::factory()->create([
            'password' => Hash::make('password')
        ]);
        $response = $this->actingAs($user)->postJson('/api/v3/change-password', $parameter);
        $response->assertStatus(200);
    }
}
