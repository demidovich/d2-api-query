<?php

namespace Tests\Mock\FindQueries;

use Tests\Mock\FindBaseQuery;

class FindCityQuery extends FindBaseQuery
{
    protected ?string $table = "city";

    protected array $allowedFields = [
        "id",
        "name",
    ];
}
