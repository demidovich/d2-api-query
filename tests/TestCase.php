<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
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

    protected function query(string $query, array $input = []): array
    {
        $results = (new $query($input))->results();

        return $results->toArray();
    }

    protected function queryItems(string $query, array $input = []): array
    {
        $results = $this->query($query, $input);

        return isset($results['data']) ? $results['data'] : null;
    }

    protected function queryFirstItem(string $query, array $input = []): ?object
    {
        $results = $this->queryItems($query, $input);

        return isset($results['0']) ? $results[0] : null;
    }

    // /**
    //  * @param string $query
    //  * @param array $input
    //  * @return Collection|Paginator
    //  */
    // protected function query(string $query, array $input = [])
    // {
    //     $results = (new $query($input))->results();

    //     return $results;
    // }

    // /**
    //  * @param string $query
    //  * @param array $input
    //  * @return Collection
    //  */
    // protected function queryItems(string $query, array $input = [])
    // {
    //     $results = $this->query($query, $input);

    //     return $results->items() ? $results->items() : $results;
    // }

    // /**
    //  * @param string $query
    //  * @param array $input
    //  * @return object|null
    //  */
    // protected function queryFirstItem(string $query, array $input = [])
    // {
    //     $results = $this->queryItems($query, $input);

    //     return isset($results['0']) ? $results[0] : null;
    // }
}
