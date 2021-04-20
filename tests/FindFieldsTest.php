<?php

namespace Tests;

use Illuminate\Validation\ValidationException;
use Tests\Mock\FindQueries\FindFieldsQuery;

class FindFieldsTest extends TestCase
{
    public function test_default()
    {
        $results = $this->findQueryFirstItem(FindFieldsQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(3, count($results));
    }

    public function test_select()
    {
        $results = $this->findQueryFirstItem(FindFieldsQuery::class, ["fields" => "id"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertArrayHasKey("id", $results);
    }

    /**
     * @dataProvider badParamProvider
     */
    public function test_bad_param_exception($value)
    {
        $this->expectException(ValidationException::class);

        $this->findQueryItems(FindFieldsQuery::class, ["fields" => $value]);
    }

    public function badParamProvider()
    {
        return [
            [25],
            ["id,"],
            [",id"],
            ["\\d"],
            ["i#d"],
        ];
    }
}
