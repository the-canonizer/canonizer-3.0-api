<?php

namespace App\Helpers;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticSearch
{
    public $elasticsearchClient;

    public function __construct()
    {
        $host     = env('ELASTICSEARCH_HOSTS', 'localhost:9200');
        $username = env('ELASTICSEARCH_BASIC_AUTH_USERNAME', null);
        $password = env('ELASTICSEARCH_BASIC_AUTH_PASSWORD', null);
        return $this->elasticsearchClient = ClientBuilder::create()
            ->setBasicAuthentication($username, $password)
            ->setHosts(['host' => $host])
            ->build();
    }

}