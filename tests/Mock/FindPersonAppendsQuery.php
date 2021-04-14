<?php

namespace Tests\Mock;

class FindPersonAppendsQuery extends BaseQuery
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
