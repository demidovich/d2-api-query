<?php

namespace Tests;

use Tests\Mock\FindQueries\FindHasOneQuery;
use Tests\Mock\ReadQueries\ReadHasOneQuery;

class HasOneRelationTest extends TestCase
{
    public function test_item()
    {
        $results = $this->readQuery(ReadHasOneQuery::class, self::PERSON_ID);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['city']);
    }

    public function test_item_without_relation_id()
    {
        $results = $this->readQuery(ReadHasOneQuery::class, self::PERSON_ID_WITHOUT_CITY);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertTrue(array_key_exists('city', $results));
        $this->assertEmpty($results['city']);
    }

    public function test_collection()
    {
        $results = $this->findQueryFirstItem(FindHasOneQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['city']);
    }
}
