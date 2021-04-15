<?php

namespace Tests;

use Tests\Mock\FindQueries\FindPersonAppendsQuery;

class FindAppendsTest extends TestCase
{
    public function test_default()
    {
        $results = $this->queryFirstItem(FindPersonAppendsQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("fullname", $results);
    }

    public function test_select()
    {
        $results = $this->queryFirstItem(FindPersonAppendsQuery::class, ["fields" => "id,fullname"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("fullname", $results);
    }

    public function test_not_select()
    {
        $results = $this->queryFirstItem(FindPersonAppendsQuery::class, ["fields" => "id"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayNotHasKey("fullname", $results);
    }
}
