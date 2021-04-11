<?php

namespace Tests;

use Tests\Mock\FindPersonQuery;
use Tests\Mock\FindPersonQueryConcept;

class DevTest extends TestCase
{
    public function test_dev()
    {
        $results = $this->queryItems(FindPersonQueryConcept::class);

        dd(
            $results
        );
    }
}
