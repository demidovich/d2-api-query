<?php

namespace Tests;

use stdClass;
use Tests\Mock\ReadQueries\ReadHasOnePrefixQuery;

class HasOnePrefixRelationTest extends TestCase
{
    public function test_item()
    {
        $results = $this->readQuery(ReadHasOnePrefixQuery::class, self::PERSON_ID);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['city']);
    }

    public function test_item_without_relation_id()
    {
        $results = $this->readQuery(ReadHasOnePrefixQuery::class, self::PERSON_ID_WITHOUT_CITY);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertTrue(array_key_exists('city', $results));
        $this->assertEmpty($results['city']);
    }
}
