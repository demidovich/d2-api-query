<?php

namespace Tests\Mock\FindQueries;

use Tests\Mock\FindBaseQuery;

class FindPersonAppendsQuery extends FindBaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "fullname" => "append|depends:first_name,last_name",
    ];

    protected function fullnameAppend(object $row): string
    {
        return "{$row->first_name} {$row->last_name}";
    }
}
