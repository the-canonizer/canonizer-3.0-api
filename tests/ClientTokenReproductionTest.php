<?php

namespace Tests;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client;

class ClientTokenReproductionTest extends TestCase
{
    public function testClientTokenReturns400()
    {
        // Use the existing client ID 2 (Password Grant) to see if it fails with client_credentials
        $response = $this->postJson('/api/v3/client-token', [
            'client_id' => '2',
            'client_secret' => 'x6UX6WOv482Ree7r4sqEdzksvoadWKp6Dmgexbs7',
        ]);

        echo "\nResponse Status: " . $response->status() . "\n";
        echo "Response Content: " . $response->getContent() . "\n";

        $response->assertStatus(401);
    }

    public function testClientTokenWithNewClient()
    {
        // Create a new client that should support client_credentials
        $client = new Client();
        $client->name = 'Test Client Credentials';
        $client->secret = 'test-secret-123';
        $client->redirect = 'http://localhost';
        $client->personal_access_client = 0;
        $client->password_client = 0;
        $client->revoked = 0;
        $client->save();

        $response = $this->postJson('/api/v3/client-token', [
            'client_id' => (string)$client->id,
            'client_secret' => 'test-secret-123',
        ]);

        echo "\nNew Client Response Status: " . $response->status() . "\n";
        echo "New Client Response Content: " . $response->getContent() . "\n";

        $response->assertStatus(200);
    }
}
