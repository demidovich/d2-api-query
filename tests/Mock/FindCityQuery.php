<?php

namespace Tests\Mock;

class FindCityQuery extends BaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "city";

    protected array $allowedFields = [
        "id",
        "name",
    ];
}
