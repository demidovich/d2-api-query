<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    const PERSON_ID = 1;
    const PERSON_ID_WITHOUT_CITY = 5;
    const PERSON_FIRST_NAME = "Jon";
    const PERSON_LAST_NAME = "Snow";

    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule;

        $capsule->addConnection(
            [
                "driver"   => "pgsql",
                "host"     => "postgres",
                "database" => "d2",
                "username" => "d2",
                "password" => "d2"
            ], 
            "default"
        );

         $capsule->setAsGlobal();
    }

    protected function db(): Connection
    {
        return Capsule::connection("default");
    }

    protected function readQuery(string $query, $key, array $input = []): array
    {
        $results = (new $query($key, $input))->results();

        return (array) $results;
    }

    protected function findQuery(string $query, array $input = []): array
    {
        $results = (new $query($input))->results();

        return $results->toArray();
    }

    protected function findQueryItems(string $query, array $input = []): array
    {
        $results = $this->findQuery($query, $input);

        return isset($results['data']) ? $results['data'] : $results;
    }

    protected function findQueryFirstItem(string $query, array $input = []): ?array
    {
        $results = $this->findQueryItems($query, $input);

        return isset($results['0']) ? (array) $results[0] : null;
    }
}
