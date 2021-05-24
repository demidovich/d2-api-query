<?php

namespace Tests\Mock\ReadQueries;

use D2\ApiQuery\MultiplePrimaryKey;
use Tests\Mock\ReadBaseQuery;

class ReadMultiplePrimaryKeyQuery extends ReadBaseQuery
{
    use MultiplePrimaryKey;

    protected string $table = "person";
    protected array $multiplePrimaryKey = [
        "first_name",
        "last_name",
    ];

    protected array $allowedFields = [
        "first_name",
        "last_name",
    ];
}
