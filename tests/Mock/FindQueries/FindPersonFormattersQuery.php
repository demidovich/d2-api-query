<?php

namespace Tests\Mock\FindQueries;

use Tests\Mock\FindBaseQuery;

class FindPersonFormattersQuery extends FindBaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "created_at" => "format:json_date"
    ];
}
