<?php

namespace Tests\Mock\Invalid;

use Tests\Mock\ReadBaseQuery;

class InvalidFieldDependencyQuery extends ReadBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "city" => "addition|depends:25",
    ];
}
