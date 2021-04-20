<?php

namespace Tests;

use Tests\Mock\FindQueries\FindPersonHasManyQuery;

class FindHasManyRelationTest extends TestCase
{
    public function test_default()
    {
        $results = $this->findQueryFirstItem(FindPersonHasManyQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['horse']);
        $this->assertEquals(2, count($results['horse']));
    }

    public function test_select()
    {
        $payload = [
            "fields" => "id,horse", 
        ];

        $results = $this->findQueryFirstItem(FindPersonHasManyQuery::class, $payload);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertNotEmpty($results['horse']);
    }

    public function test_not_select()
    {
        $payload = [
            "fields" => "id", 
        ];

        $results = $this->findQueryFirstItem(FindPersonHasManyQuery::class, $payload);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertTrue(! isset($results['horse']));
    }
}
