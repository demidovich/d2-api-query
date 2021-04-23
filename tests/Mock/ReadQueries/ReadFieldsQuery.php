<?php

namespace Tests\Mock\ReadQueries;

use Tests\Mock\ReadBaseQuery;

class ReadFieldsQuery extends ReadBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "first_name",
        "last_name",
    ];
}
