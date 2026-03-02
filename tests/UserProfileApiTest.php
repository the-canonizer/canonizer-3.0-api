<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserProfileApiTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGetuserProfileWithEmptyParam()
    {
        print sprintf("Test with invalid parameter passed");
        $_res = $this->call('GET', '/api/v3/user/supports', []);
        $_res->assertStatus(404);
    }

    public function testGetuserProfileWithValidParam()
    {
        print sprintf("Test with valid parameter passed");
        $user = User::factory()->create();
        $_res = $this->actingAs($user)->get('/api/v3/user/supports/1?canon=');
        $_res->assertStatus(200);
    }
}
