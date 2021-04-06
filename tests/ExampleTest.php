<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

class ExampleTest extends UnitTestCase
{
    public function test_command()
    {
        dd($this->db()->table("person")->get());

        $this->assertTrue(true);
    }
}
