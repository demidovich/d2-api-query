<?php

namespace Tests\Mock\Invalid;

use Tests\Mock\ReadBaseQuery;

class InvalidFieldFormatterQuery extends ReadBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "first_name" => "format:unexists_formatter",
    ];
}
