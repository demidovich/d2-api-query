<?php

namespace Tests;

use Tests\Mock\FindQueries\FindPersonRelationQuery;

class FindRelationsTest extends TestCase
{
    public function test_default()
    {
        $results = $this->queryFirstItem(FindPersonRelationQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['city']);
    }

    public function test_select()
    {
        $payload = [
            "fields" => "id,city", 
        ];

        $results = $this->queryFirstItem(FindPersonRelationQuery::class, $payload);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['city']);
    }

    public function test_not_select()
    {
        $payload = [
            "fields" => "id", 
        ];

        $results = $this->queryFirstItem(FindPersonRelationQuery::class, $payload);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertTrue(! isset($results['city']));
    }
}
