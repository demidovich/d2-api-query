<?php

namespace Tests\Mock\FindQueries;

use Tests\Mock\FindBaseQuery;

class FindAdditionQuery extends FindBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "fullname" => "addition|depends:first_name,last_name",
    ];

    protected function fullnameAddition(object $row): string
    {
        return "{$row->first_name} {$row->last_name}";
    }
}
