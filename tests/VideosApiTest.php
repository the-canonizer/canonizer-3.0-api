<?php

use App\Models\User;

class VideosApiTest extends TestCase
{
    public function testVideosApiResults() {
        $user = User::factory()->create();
        $accessToken = $user->createToken('TestToken')->accessToken;
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $this->actingAs($user)->get('/api/v3/videos', $headers);
        $this->response->assertStatus(200);
    }

    public function testVideosApiResponseStructure() {
        $user = User::factory()->create();
        $accessToken = $user->createToken('TestToken')->accessToken;
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $this->actingAs($user)->get('/api/v3/videos', $headers);
        $this->response->assertStatus(200)->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'id',
                    'title',
                    'type',
                    'videos' => [
                        [
                            'id',
                            'thumbnail',
                            'title'
                        ]
                    ]
                ]
            ],
        ]);
    }

    public function testVideosByCategory()
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('TestToken')->accessToken;
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $this->actingAs($user)->get('/api/v3/videos/consiousness/1', $headers);
        $this->response->assertStatus(200);
    }

    public function testVideosByWrongCategory()
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('TestToken')->accessToken;
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $this->actingAs($user)->get('/api/v3/videos/consiousness/433233', $headers);
        $this->response->assertStatus(404);
    }

    public function testVideosByCategoryApiStructure()
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('TestToken')->accessToken;
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $this->actingAs($user)->get('/api/v3/videos/consiousness/1', $headers);
        $this->response->assertStatus(200)->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                [
                    'id',
                    'title',
                    'type',
                    'videos' => [
                        [
                            'id',
                            'thumbnail',
                            'title',
                            'resolutions' => [
                                [
                                    'id',
                                    'title',
                                    'link'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ]);
    }
}
