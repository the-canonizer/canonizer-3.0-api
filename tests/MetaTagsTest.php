<?php

class GetMetaTagsTest extends TestCase
{
    public function testGetMetatagsWithoutPayload()
    {
        $payload = [];
        print sprintf("\nTest without payload");

        $this->post('/api/v3/meta-tags', $payload);
        $this->assertEquals(400,  $this->response->status());
    }

    public function testGetMetatagsForStaticPage()
    {
        $payload = [
            'page_name' => 'Home'
        ];
        print sprintf("\nTest for static pages only");

        $this->post('/api/v3/meta-tags', $payload);
        $this->assertEquals(200,  $this->response->status());
    }

    public function testGetMetatagsForDynamicPage()
    {
        $payload = [
            'page_name' => 'TopicDetailsPage'
        ];
        print sprintf("\nTest for dynamic pages only");

        $this->post('/api/v3/meta-tags', $payload);
        $this->assertEquals(400,  $this->response->status());
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

        $this->post('/api/v3/meta-tags', $payload);
        $this->assertEquals(404,  $this->response->status());
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

        $this->post('/api/v3/meta-tags', $payload);
        $this->assertEquals(400,  $this->response->status());
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

        $this->post('/api/v3/meta-tags', $payload);
        $this->assertEquals(400,  $this->response->status());
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

        $this->post('/api/v3/meta-tags', $payload);
        $this->assertEquals(400,  $this->response->status());
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

        $this->post('/api/v3/meta-tags', $payload);
        $this->assertEquals(400,  $this->response->status());
    }
}
