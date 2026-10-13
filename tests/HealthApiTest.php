<?php

class HealthApiTest extends TestCase
{
    /**
     * GET /api/health returns app status.
     *
     * @return void
     */
    public function testHealthReturnsOkStatus()
    {
        $this->get('/api/health');

        $this->assertResponseStatus(200);
        $this->seeJson(['status' => 'ok']);
        $this->seeJsonStructure(['status', 'app', 'framework', 'timestamp']);
    }
}
