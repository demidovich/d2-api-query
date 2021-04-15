<?php

namespace Tests;

use Illuminate\Validation\ValidationException;
use Tests\Mock\FindQueries\FindPersonQuery;

class FindCountTest extends TestCase
{
    public function test_default()
    {
        $count   = $this->db()->table("person")->count();
        $results = $this->queryItems(FindPersonQuery::class);

        $this->assertEquals($count, count($results));
    }

    public function test_select_all()
    {
        $count   = $this->db()->table("person")->count();
        $results = $this->queryItems(FindPersonQuery::class, ["count" => 0]);

        $this->assertEquals($count, count($results));
    }

    public function test_select_value()
    {
        $results = $this->queryItems(FindPersonQuery::class, ["count" => 3]);

        $this->assertEquals(3, count($results));
    }

    /**
     * @dataProvider badParamProvider
     */
    public function test_bad_param_exception($value)
    {
        $this->expectException(ValidationException::class);

        $this->queryItems(FindPersonQuery::class, ["count" => $value]);
    }

    public function badParamProvider()
    {
        return [
            [25.1],
            ["a"],
            [",5"],
            ["\\5"],
            ["#5"],
        ];
    }
}
