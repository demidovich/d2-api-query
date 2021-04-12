<?php

namespace Tests\Mock;

class FindPersonFormattersQuery extends BaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "created_at" => "format:json_date"
    ];

    protected function jsonDateFormatter($value)
    {
        return $value ? date("c", strtotime($value)) : null;
    }
}
