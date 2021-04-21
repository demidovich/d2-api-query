<?php

namespace Tests;

use RuntimeException;
use Tests\Mock\Invalid\InvalidFieldDependencyQuery;
use Tests\Mock\Invalid\InvalidFieldFormatterQuery;
use Tests\Mock\Invalid\InvalidFieldOptionQuery;

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
}
