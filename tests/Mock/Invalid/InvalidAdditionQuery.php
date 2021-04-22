<?php

namespace Tests\Mock\Invalid;

use Tests\Mock\ReadBaseQuery;

class InvalidAdditionQuery extends ReadBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "missing_method" => "addition",
    ];
}
