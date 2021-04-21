<?php

namespace Tests;

use Illuminate\Validation\ValidationException;
use Tests\Mock\FindQueries\FindFieldsQuery;
use Tests\Mock\ReadQueries\ReadFieldsQuery;

class FieldsTest extends TestCase
{
    public function test_item_default()
    {
        $results = $this->readQuery(ReadFieldsQuery::class, self::PERSON_ID);

        $this->assertNotEmpty($results);
        $this->assertEquals(3, count($results));
    }

    public function test_collection_default()
    {
        $results = $this->findQueryFirstItem(FindFieldsQuery::class);

        $this->assertNotEmpty($results);
        $this->assertEquals(3, count($results));
    }

    public function test_item_specific_fields()
    {
        $results = $this->readQuery(ReadFieldsQuery::class, self::PERSON_ID, ["fields" => "id"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertArrayHasKey("id", $results);
    }

    public function test_collection_specific_fields()
    {
        $results = $this->findQueryFirstItem(FindFieldsQuery::class, ["fields" => "id"]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, count($results));
        $this->assertArrayHasKey("id", $results);
    }

    public function test_item_not_allowed_field_exception()
    {
        $this->expectException(ValidationException::class);

        $this->readQuery(ReadFieldsQuery::class, self::PERSON_ID, ["fields" => 'not_allowed_field']);
    }

    public function test_collection_not_allowed_field_exception()
    {
        $this->expectException(ValidationException::class);

        $this->findQueryItems(FindFieldsQuery::class, ["fields" => 'not_allowed_field']);
    }

    /**
     * @dataProvider badParamProvider
     */
    public function test_item_bad_field_exception($value)
    {
        $this->expectException(ValidationException::class);

        $this->readQuery(ReadFieldsQuery::class, self::PERSON_ID, ["fields" => $value]);
    }

    /**
     * @dataProvider badParamProvider
     */
    public function test_collection_bad_field_exception($value)
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
