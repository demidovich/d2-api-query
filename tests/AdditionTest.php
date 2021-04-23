<?php

namespace Tests;

use Tests\Mock\FindQueries\FindAdditionQuery;
use Tests\Mock\ReadQueries\ReadAdditionQuery;

class AdditionTest extends TestCase
{
    public function test_item()
    {
        $results = $this->readQuery(ReadAdditionQuery::class, self::PERSON_ID, ["fields" => "id,fullname"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("fullname", $results);
    }

    public function test_collection()
    {
        $results = $this->findQueryFirstItem(FindAdditionQuery::class, ["fields" => "id,fullname"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("fullname", $results);
    }
}
