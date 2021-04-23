<?php

namespace Tests\Mock\Invalid;

use Tests\Mock\ReadBaseQuery;

class InvalidFieldOptionQuery extends ReadBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "city" => "addition1",
    ];
}
