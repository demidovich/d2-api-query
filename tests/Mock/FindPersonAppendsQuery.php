<?php

namespace Tests\Mock;

class FindPersonAppendsQuery extends BaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "fullname" => "append:first_name,last_name",
    ];

    protected function fullnameAppend(object $row): void
    {
        $row->fullname = "{$row->first_name} {$row->last_name}";
    }
}
