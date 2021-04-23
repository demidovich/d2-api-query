<?php

namespace Tests;

use Tests\Mock\FindQueries\FindFormatQuery;
use Tests\Mock\ReadQueries\ReadFormatQuery;

class FormatTest extends TestCase
{
    public function test_item()
    {
        $results = $this->readQuery(ReadFormatQuery::class, self::PERSON_ID);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("created_at", $results);

        $this->assertTrue(str_contains($results['created_at'], 'T'));
    }

    public function test_collection()
    {
        $results = $this->findQueryFirstItem(FindFormatQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("created_at", $results);

        $this->assertTrue(str_contains($results['created_at'], 'T'));
    }
}
