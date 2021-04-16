<?php

namespace Tests;

use Tests\Mock\FindQueries\FindPersonAdditionsJoinQuery;
use Tests\Mock\FindQueries\FindPersonAdditionsQuery;

class FindAdditionsJointTest extends TestCase
{
    public function test_default()
    {
        $results = $this->queryFirstItem(FindPersonAdditionsJoinQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertTrue(isset($results["city"]["id"]));
    }

    public function test_select()
    {
        $results = $this->queryFirstItem(FindPersonAdditionsJoinQuery::class, ["fields" => "id,city"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertTrue(isset($results["city"]["id"]));
    }

    public function test_not_select()
    {
        $results = $this->queryFirstItem(FindPersonAdditionsQuery::class, ["fields" => "id"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertFalse(isset($results["city"]["id"]));
    }
}
