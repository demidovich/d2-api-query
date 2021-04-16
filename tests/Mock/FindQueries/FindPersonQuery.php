<?php

namespace Tests\Mock\FindQueries;

use Tests\Mock\FindBaseQuery;

class FindPersonQuery extends FindBaseQuery
{
    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "first_name",
        "last_name",
    ];
}
