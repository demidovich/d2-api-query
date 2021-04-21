<?php

namespace Tests\Mock\FindQueries;

use Tests\Mock\FindBaseQuery;

class FindFieldsQuery extends FindBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "first_name",
        "last_name",
    ];
}
