<?php

namespace Tests;

use App\Models\User;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\MetaTag;
use App\Models\Topic;
use App\Models\Camp;

class GetMetaTagsTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        
        // Seed MetaTag for static pages
        MetaTag::create([
            'page_name' => 'Home',
            'title' => 'Home Page',
            'description' => 'Home Page Description',
            'is_static' => 1
        ]);

        // Seed MetaTag for dynamic pages
        MetaTag::create([
            'page_name' => 'TopicDetailsPage',
            'title' => 'Topic: [topic_name]',
            'description' => '[topic_description]',
            'is_static' => 0
        ]);

        MetaTag::create([
            'page_name' => 'VideosPage',
            'title' => 'Video: [video_name]',
            'description' => '[video_name] Description',
            'is_static' => 0
        ]);

        MetaTag::create([
            'page_name' => 'SearchResultsPage',
            'title' => 'Search Results for [keywords]',
            'description' => 'Search Results for [keywords]',
            'is_static' => 0
        ]);

        // Ensure topic 904 exists for dynamic tests
        if (!Topic::where('topic_num', 904)->exists()) {
            Topic::factory()->create(['topic_num' => 904, 'topic_name' => 'Test Topic']);
        }
        if (!Camp::where('topic_num', 904)->where('camp_num', 1)->exists()) {
            Camp::factory()->create(['topic_num' => 904, 'camp_num' => 1, 'camp_name' => 'Agreement']);
        }
    }

    public function testGetMetatagsWithoutPayload()
    {
        $payload = [];
        print sprintf("\nTest without payload");
        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/meta-tags',$payload,$header);
        //  dd($response);
        $response->assertStatus(400);
    }

    public function testGetMetatagsForStaticPage()
    {
        $payload = [
            'page_name' => 'Home'
        ];
        print sprintf("\nTest for static pages only");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/meta-tags',$payload,$header);
        $response->assertStatus(200);
    }
    
    public function testGetMetatagsForDynamicPageWithValidData()
    {
        $payload = [
            'page_name' => 'TopicDetailsPage',
            "keys" => [
                "topic_num" => 904,
                "camp_num" => 1,
            ]
        ];
        print sprintf("\nTest for dynamic pages with valid data");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer ' . $token;
        $response = $this->actingAs($user)->post('/api/v3/meta-tags', $payload, $header);
        $response->assertStatus(200);
    }

    public function testGetMetatagsForDynamicPage()
    {
        $payload = [
            'page_name' => 'TopicDetailsPage'
        ];
        print sprintf("\nTest for dynamic pages only");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/meta-tags',$payload,$header);
        $response->assertStatus(400);
    }

    public function testGetMetatagsForPageNotFound()
    {
        $payload = [
            'page_name' => 'TopicDetailsPageas',
            "keys" => [
                "topic_num" => 88,
                "camp_num" => 1,
            ]
        ];
        print sprintf("\nTest for page not found");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/meta-tags',$payload,$header);
        $response->assertStatus(404);
    }

    public function testForCheckPageNameAlpha()
    {
        $payload = [
            'page_name' => 'TopicDetailsPage1',
            "keys" => [
                "topic_num" => 88,
                "camp_num" => 1,
            ]
        ];
        print sprintf("\nTest to check if page name is not alphabetic");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/meta-tags',$payload,$header);
        $response->assertStatus(400);
    }

    public function testIfKeysAreNotGreaterThanZero()
    {
        $payload = [
            'page_name' => 'TopicDetailsPage',
            "keys" => [
                "topic_num" => 0,
                "camp_num" => 0,
            ]
        ];
        print sprintf("\nTest to check if topic_num & camp_num are not greater than zero");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/meta-tags',$payload,$header);
        $response->assertStatus(400);
    }

    public function testIfTopicNumIsNotPresent()
    {
        $payload = [
            'page_name' => 'TopicDetailsPage',
            "keys" => [
                "camp_num" => 0,
            ]
        ];
        print sprintf("\nTest to check if topic_num is not present");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/meta-tags',$payload,$header);
        $response->assertStatus(400);
    }

    public function testIfCampNumIsNotPresent()
    {
        $payload = [
            'page_name' => 'TopicDetailsPage',
            "keys" => [
                "camp_num" => 0,
            ]
        ];
        print sprintf("\nTest to check if camp_num is not present");

        $user = User::factory()->make();
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/meta-tags',$payload,$header);
        $response->assertStatus(400);
    }
}
