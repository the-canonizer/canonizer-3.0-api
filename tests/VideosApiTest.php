<?php

namespace Tests;

use App\Models\User;

class VideosApiTest extends TestCase
{
    public function testVideosApiResults() {
        $user = User::factory()->create(['status' => 1]);
        $category = \App\Models\Category::factory()->create();
        $video = \App\Models\Video::factory()->create();
        $category->videos()->attach($video->id);

        $response = $this->actingAs($user)->get('/api/v3/videos');
        $response->assertStatus(200);
    }

    public function testVideosApiResponseStructure() {
        $user = User::factory()->create(['status' => 1]);
        $category = \App\Models\Category::factory()->create();
        $video = \App\Models\Video::factory()->create();
        $category->videos()->attach($video->id);

        $response = $this->actingAs($user)->get('/api/v3/videos');
        $response->assertStatus(200)->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'type',
                    'videos' => [
                        '*' => [
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
        $user = User::factory()->create(['status' => 1]);
        $category = \App\Models\Category::factory()->create(['title' => 'consciousness']);
        $video = \App\Models\Video::factory()->create();
        $resolution = \App\Models\Resolution::factory()->create();
        $video->resolutions()->attach($resolution->id);
        $category->videos()->attach($video->id);

        $response = $this->actingAs($user)->get('/api/v3/videos/consciousness/' . $category->id);
        $response->assertStatus(200);
    }

    public function testVideosByWrongCategory()
    {
        $user = User::factory()->create(['status' => 1]);
        $response = $this->actingAs($user)->get('/api/v3/videos/consciousness/433233');
        $response->assertStatus(404);
    }

    public function testVideosByCategoryApiStructure()
    {
        $user = User::factory()->create(['status' => 1]);
        $category = \App\Models\Category::factory()->create(['title' => 'consciousness']);
        $video = \App\Models\Video::factory()->create();
        $resolution = \App\Models\Resolution::factory()->create();
        $video->resolutions()->attach($resolution->id);
        $category->videos()->attach($video->id);

        $response = $this->actingAs($user)->get('/api/v3/videos/consciousness/' . $category->id);
        $response->assertStatus(200)->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'type',
                    'videos' => [
                        '*' => [
                            'id',
                            'thumbnail',
                            'title',
                            'resolutions' => [
                                '*' => [
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
