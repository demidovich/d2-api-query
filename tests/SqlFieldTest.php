<?php

namespace Tests;

use Tests\Mock\ReadQueries\ReadSqlFieldQuery;

class SqlFieldTest extends TestCase
{
    public function test_item_default()
    {
        $results = (new ReadSqlFieldQuery(self::PERSON_ID, []))->results();

        $this->assertNotEmpty($results);
        $this->assertTrue(property_exists($results, "full_name"));
        $this->assertEquals("$results->first_name $results->last_name", $results->full_name);
    }
}
