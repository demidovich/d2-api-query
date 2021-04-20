<?php

namespace Tests;

use Tests\Mock\FindQueries\FindHasManyQuery;
use Tests\Mock\ReadQueries\ReadHasManyQuery;

class HasManyRelationTest extends TestCase
{
    public function test_item()
    {
        $results = $this->readQuery(ReadHasManyQuery::class, self::PERSON_ID);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['horse']);
        $this->assertEquals(2, count($results['horse']));
    }

    public function test_collection()
    {
        $results = $this->findQueryFirstItem(FindHasManyQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['horse']);
        $this->assertEquals(2, count($results['horse']));
    }
}
