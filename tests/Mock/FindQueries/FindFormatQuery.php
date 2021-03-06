<?php

namespace Tests\Mock\FindQueries;

use Tests\Mock\FindBaseQuery;

class FindFormatQuery extends FindBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "created_at" => "format:json_date"
    ];
}
