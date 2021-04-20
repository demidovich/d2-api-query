<?php

namespace Tests\Mock\FindQueries;

use Tests\Mock\FindBaseQuery;

class FindCityQuery extends FindBaseQuery
{
    protected ?string $table = "city";
    protected  string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "name",
    ];
}
