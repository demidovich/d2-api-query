<?php

namespace Tests\Mock\FindQueries;

use Tests\Mock\ReadBaseQuery;

class ReadPersonQuery extends ReadBaseQuery
{
    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "first_name",
        "last_name",
    ];
}
