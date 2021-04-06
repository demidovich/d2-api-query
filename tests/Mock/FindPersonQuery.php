<?php

namespace Tests\Mock;

class FindPersonQuery extends BaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "first_name",
    ];

    protected array $rules = [
        "city_id" => "nullable|integer",
    ];
}
