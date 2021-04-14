<?php

namespace Tests;

use Tests\Mock\FindPersonAppendsJoinQuery;
use Tests\Mock\FindPersonAppendsQuery;

class FindAppendsJointTest extends TestCase
{
    public function test_default()
    {
        $results = $this->queryFirstItem(FindPersonAppendsJoinQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertTrue(isset($results["city"]["id"]));
    }

    public function test_select()
    {
        $results = $this->queryFirstItem(FindPersonAppendsJoinQuery::class, ["fields" => "id,city"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertTrue(isset($results["city"]["id"]));
    }

    public function test_not_select()
    {
        $results = $this->queryFirstItem(FindPersonAppendsQuery::class, ["fields" => "id"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertFalse(isset($results["city"]["id"]));
    }
}
