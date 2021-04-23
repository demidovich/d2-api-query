<?php

namespace Tests;

use RuntimeException;
use Tests\Mock\Invalid\InvalidAdditionQuery;
use Tests\Mock\Invalid\InvalidFieldDependencyQuery;
use Tests\Mock\Invalid\InvalidFieldFormatterQuery;
use Tests\Mock\Invalid\InvalidFieldOptionQuery;
use Tests\Mock\Invalid\InvalidRelationQuery;

class ExceptionTest extends TestCase
{
    public function test_bad_field_option()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidFieldOptionQuery::class, self::PERSON_ID);
    }

    public function test_bad_field_dependency()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidFieldDependencyQuery::class, self::PERSON_ID);
    }

    public function test_bad_field_formatter()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidFieldFormatterQuery::class, self::PERSON_ID);
    }

    public function test_missing_addition_method()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidAdditionQuery::class, self::PERSON_ID, ["fields" => "id,missing_method"]);
    }

    public function test_missing_relation_method()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidRelationQuery::class, self::PERSON_ID, ["fields" => "id,missing_method"]);
    }

    public function test_bad_contract_relation_method()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidRelationQuery::class, self::PERSON_ID, ["fields" => "id,bad_contract_method"]);
    }

    public function test_missing_item_key_relation_method()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidRelationQuery::class, self::PERSON_ID, ["fields" => "id,missing_item_relation_key"]);
    }

    public function test_missing_collection_key_relation_method()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidRelationQuery::class, self::PERSON_ID, ["fields" => "id,missing_collection_relation_key"]);
    }
}
