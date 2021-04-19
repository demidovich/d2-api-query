<?php

namespace Tests;

use Tests\Mock\FindQueries\FindPersonHasOneQuery;

class FindHasOneRelationTest extends TestCase
{
    public function test_default()
    {
        $results = $this->queryFirstItem(FindPersonHasOneQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['city']);
    }

    public function test_select()
    {
        $payload = [
            "fields" => "id,city", 
        ];

        $results = $this->queryFirstItem(FindPersonHasOneQuery::class, $payload);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['city']);
    }

    public function test_not_select()
    {
        $payload = [
            "fields" => "id", 
        ];

        $results = $this->queryFirstItem(FindPersonHasOneQuery::class, $payload);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertTrue(! isset($results['city']));
    }
}
