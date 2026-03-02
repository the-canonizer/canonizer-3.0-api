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
        $response = $this->call('GET', '/api/v3/user/supports', []);
        $response->assertStatus(404);
    }

    public function testGetuserProfileWithValidParam()
    {
        print sprintf("Test with valid parameter passed");
        $user = User::factory()->create(['status' => 1]);
        $nickname = \App\Models\Nickname::factory()->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->get('/api/v3/user/supports/' . $nickname->id . '?canon=');
        $response->assertStatus(200);
    }
}
