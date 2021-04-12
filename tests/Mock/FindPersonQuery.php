<?php

namespace Tests\Mock;

class FindPersonQuery extends BaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "first_name",
        "last_name",
    ];
}
