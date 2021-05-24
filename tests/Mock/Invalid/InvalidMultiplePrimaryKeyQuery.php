<?php

namespace Tests\Mock\Invalid;

use D2\ApiQuery\MultiplePrimaryKey;
use Tests\Mock\ReadBaseQuery;

class InvalidMultiplePrimaryKeyQuery extends ReadBaseQuery
{
    use MultiplePrimaryKey;

    protected string $table = "person";

    // Missing configuration
    //
    // protected array $multiplePrimaryKey = [
    //     "first_name",
    //     "last_name",
    // ];

    protected array $allowedFields = [
        "first_name",
        "last_name",
    ];
}
