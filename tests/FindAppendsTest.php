<?php

namespace Tests;

use Tests\Mock\FindPersonAppendsQuery;

class FindAppendsTest extends TestCase
{
    public function test_not_set()
    {
        $results = $this->queryFirstItem(FindPersonAppendsQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("fullname", $results);
    }

    public function test_set_one()
    {
        $results = $this->queryFirstItem(FindPersonAppendsQuery::class, ["fields" => "id,fullname"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("fullname", $results);
    }
}
