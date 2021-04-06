<?php

namespace Tests;

use Tests\Mock\FindPersonQuery;

class FindApiQueryTest extends TestCase
{
    public function test_all_fields()
    {
        $results = $this->queryItems(FindPersonQuery::class);

        $this->assertNotEmpty(count($results) > 0);
    }
}
