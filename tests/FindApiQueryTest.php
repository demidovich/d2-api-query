<?php

namespace Tests;

use Tests\Mock\FindPersonQuery;

class FindApiQueryTest extends TestCase
{
    public function test_find()
    {
        $results = (new FindPersonQuery([]))->results();

        dd($results->toArray());

        $this->assertTrue(true);
    }
}
