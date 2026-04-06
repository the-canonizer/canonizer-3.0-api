<?php

namespace Tests;

use App\Helpers\ElasticSearch;
use App\Models\Search;
use ReflectionClass;

class ElasticSearchFixTest extends TestCase
{
    /**
     * Reset the static $indexChecked flag between tests
     */
    protected function resetIndexCheckedFlag()
    {
        $reflection = new ReflectionClass(ElasticSearch::class);
        $property = $reflection->getProperty('indexChecked');
        $property->setAccessible(true);
        $property->setValue(null, false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetIndexCheckedFlag();
    }

    /**
     * Test that ElasticSearch helper has the ensureIndexExists method
     */
    public function testEnsureIndexExistsMethodExists()
    {
        $this->assertTrue(
            method_exists(ElasticSearch::class, 'ensureIndexExists'),
            'ElasticSearch helper should have ensureIndexExists() method'
        );
    }

    /**
     * Test that ensureIndexExists is a static method with correct signature
     */
    public function testEnsureIndexExistsIsStatic()
    {
        $reflection = new ReflectionClass(ElasticSearch::class);
        $method = $reflection->getMethod('ensureIndexExists');
        $this->assertTrue($method->isStatic(), 'ensureIndexExists() should be a static method');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'ensureIndexExists() should accept 1 parameter');
    }

    /**
     * Test that the static $indexChecked flag exists and defaults to false
     */
    public function testIndexCheckedFlagExists()
    {
        $reflection = new ReflectionClass(ElasticSearch::class);
        $this->assertTrue(
            $reflection->hasProperty('indexChecked'),
            'ElasticSearch should have $indexChecked static property'
        );

        $property = $reflection->getProperty('indexChecked');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue(), '$indexChecked should default to false');
    }

    /**
     * Test that the index name constant is correct
     */
    public function testIndexNameIsCorrect()
    {
        $this->assertEquals('canonizer_elastic_search', ElasticSearch::$indexName);
    }

    /**
     * Test that Search::getSearchData returns empty result instead of throwing exception
     * when ElasticSearch is unavailable (simulated by testing environment)
     */
    public function testGetSearchDataReturnsEmptyOnFailure()
    {
        $result = Search::getSearchData('nonexistent_term_xyz', ['topic'], 20, 1, true);

        $this->assertIsArray($result, 'getSearchData should return an array even on failure');
        $this->assertArrayHasKey('data', $result, 'Result should have "data" key');
        $this->assertArrayHasKey('count', $result, 'Result should have "count" key');
        $this->assertArrayHasKey('type_counts', $result, 'Result should have "type_counts" key');
    }

    /**
     * Test that Search::getSearchData does not throw an exception (no 500 error)
     */
    public function testGetSearchDataDoesNotThrowException()
    {
        $exceptionThrown = false;
        try {
            $result = Search::getSearchData('test', ['topic', 'camp', 'statement', 'nickname'], 20, 1, true);
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertFalse($exceptionThrown, 'getSearchData should not throw an exception when ES is unavailable');
    }

    /**
     * Test that the search API endpoint returns a valid response (not 500)
     * even when ElasticSearch index might not exist
     */
    public function testSearchApiDoesNotReturn500()
    {
        $response = $this->get('/api/v3/search?term=Theories');

        $this->assertNotEquals(500, $response->status(), 'Search API should not return 500 error');
        $this->assertContains($response->status(), [200, 400, 404], 'Search API should return a handled status code');
    }

    /**
     * Test that the search API returns proper JSON structure
     */
    public function testSearchApiReturnsValidJsonStructure()
    {
        $response = $this->get('/api/v3/search?term=test');

        $response->assertJsonStructure([
            'status_code',
        ]);
    }

    /**
     * Test search with empty term
     */
    public function testSearchApiWithEmptyTerm()
    {
        $response = $this->get('/api/v3/search?term=');

        $this->assertNotEquals(500, $response->status(), 'Search with empty term should not return 500');
    }

    /**
     * Test that the import command class exists and has the correct signature
     */
    public function testImportCommandExists()
    {
        $this->assertTrue(
            class_exists(\App\Console\Commands\AddExsistingDataToElasticSearch::class),
            'AddExsistingDataToElasticSearch command should exist'
        );
    }

    /**
     * Test that ElasticSearch ingestData method does not throw in testing environment
     */
    public function testIngestDataDoesNotThrowInTestingEnvironment()
    {
        $exceptionThrown = false;
        try {
            ElasticSearch::ingestData(
                'test-doc-1',
                'topic',
                'Test Topic',
                1, 1, '',
                time(),
                'main',
                '',
                true,
                false
            );
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertFalse($exceptionThrown, 'ingestData should not throw in testing environment');
    }

    /**
     * Test that ElasticSearch deleteData method does not throw in testing environment
     */
    public function testDeleteDataDoesNotThrowInTestingEnvironment()
    {
        $exceptionThrown = false;
        try {
            ElasticSearch::deleteData('test-doc-1');
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertFalse($exceptionThrown, 'deleteData should not throw in testing environment');
    }
}
