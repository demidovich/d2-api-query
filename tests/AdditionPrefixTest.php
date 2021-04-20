<?php

namespace Tests;

use Tests\Mock\FindQueries\FindAdditionPrefixQuery;
use Tests\Mock\ReadQueries\ReadAdditionPrefixQuery;

class AdditionPrefixTest extends TestCase
{
    public function test_item()
    {
        $results = $this->readQuery(ReadAdditionPrefixQuery::class, self::PERSON_ID, ["fields" => "id,city"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertTrue(isset($results["city"]["id"]));
    }

    public function test_collection()
    {
        $results = $this->findQueryFirstItem(FindAdditionPrefixQuery::class, ["fields" => "id,city"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertTrue(isset($results["city"]["id"]));
    }
}
