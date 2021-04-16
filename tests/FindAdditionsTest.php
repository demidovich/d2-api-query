<?php

namespace Tests;

use Tests\Mock\FindQueries\FindPersonAdditionsQuery;

class FindAdditionsTest extends TestCase
{
    public function test_default()
    {
        $results = $this->queryFirstItem(FindPersonAdditionsQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("fullname", $results);
    }

    public function test_select()
    {
        $results = $this->queryFirstItem(FindPersonAdditionsQuery::class, ["fields" => "id,fullname"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("fullname", $results);
    }

    public function test_not_select()
    {
        $results = $this->queryFirstItem(FindPersonAdditionsQuery::class, ["fields" => "id"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayNotHasKey("fullname", $results);
    }
}
