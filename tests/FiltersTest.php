<?php

namespace Tests;

use Illuminate\Validation\ValidationException;
use Tests\Mock\FindQueries\FindFiltersQuery;

class FiltersTest extends TestCase
{
    public function test_unselect()
    {
        $results = $this->findQueryItems(FindFiltersQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(5, count($results));
    }

    public function test_select_value()
    {
        $results = $this->findQueryItems(FindFiltersQuery::class, ["id" => self::PERSON_ID]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
    }

    public function test_collection_bad_field_exception()
    {
        $this->expectException(ValidationException::class);

        $this->findQueryItems(FindFiltersQuery::class, ["id" => "not_integer"]);
    }
}
