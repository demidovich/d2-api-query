<?php

namespace Tests\Mock\ReadQueries;

use Tests\Mock\ReadBaseQuery;

class ReadSqlFieldQuery extends ReadBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "first_name",
        "last_name",
        "full_name" => "sql:first_name || ' ' || last_name"
    ];
}
