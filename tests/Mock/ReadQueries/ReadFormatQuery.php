<?php

namespace Tests\Mock\ReadQueries;

use Tests\Mock\ReadBaseQuery;

class ReadFormatQuery extends ReadBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "created_at" => "format:json_date"
    ];
}
