<?php

namespace Tests;

use Tests\Mock\FindPersonFormattersQuery;

class FindFormattersTest extends TestCase
{
    public function test_default()
    {
        $results = $this->queryFirstItem(FindPersonFormattersQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(2, count($results));
        $this->assertArrayHasKey("id", $results);
        $this->assertArrayHasKey("created_at", $results);

        $this->assertTrue(str_contains($results['created_at'], 'T'));
    }

    public function test_select()
    {
        $results = $this->queryFirstItem(FindPersonFormattersQuery::class, ["fields" => "created_at"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertArrayHasKey("created_at", $results);
    }
}
