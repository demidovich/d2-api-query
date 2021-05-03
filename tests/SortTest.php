<?php

namespace Tests;

use Illuminate\Validation\ValidationException;
use Tests\Mock\FindQueries\FindFieldsQuery;

class SortTest extends TestCase
{
    public function test_without_sort()
    {
        $query = new FindFieldsQuery([]);

        $this->assertFalse(
            $query->hasSort("created_at")
        );
    }

    public function test_with_sort()
    {
        $query = new FindFieldsQuery([
            "sort" => [
                "created_at" => "asc",
            ],
        ]);

        $this->assertTrue($query->hasSort("created_at"));
        $this->assertEquals($query->sortDirection("created_at"), "asc");
    }

    /**
     * @dataProvider badParamProvider
     */
    public function test_validation_exception($value)
    {
        $this->expectException(ValidationException::class);

        $query = new FindFieldsQuery(["sort" => $value]);
        $query->results();
    }

    public function badParamProvider()
    {
        return [
            [[15 => "asc"]],
            [["`id`" => "asc"]],
            [["id" => ["1"]]],
            [["id" => "assc"]],
        ];
    }
}
