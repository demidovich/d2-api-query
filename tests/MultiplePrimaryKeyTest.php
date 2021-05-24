<?php

namespace Tests;

use RuntimeException;
use Tests\Mock\Invalid\InvalidMultiplePrimaryKeyQuery;
use Tests\Mock\ReadQueries\ReadMultiplePrimaryKeyQuery;

class MultiplePrimaryKeyTest extends TestCase
{
    public function test_success()
    {
        $results = $this->readQuery(ReadMultiplePrimaryKeyQuery::class, [
            "first_name" => self::PERSON_FIRST_NAME,
            "last_name"  => self::PERSON_LAST_NAME,
        ]);

        $this->assertNotEmpty($results);
        $this->assertEquals(self::PERSON_FIRST_NAME, $results["first_name"]);
    }

    public function test_missing_multiple_primary_key_property()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidMultiplePrimaryKeyQuery::class, [
            "first_name" => self::PERSON_FIRST_NAME,
            "last_name"  => self::PERSON_LAST_NAME,
        ]);
    }

    public function test_bad_value_type()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidMultiplePrimaryKeyQuery::class, self::PERSON_FIRST_NAME);
    }

    public function test_missing_part_of_value()
    {
        $this->expectException(RuntimeException::class);

        $this->readQuery(InvalidMultiplePrimaryKeyQuery::class, self::PERSON_FIRST_NAME);
    }
}
