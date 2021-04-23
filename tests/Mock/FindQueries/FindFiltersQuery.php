<?php

namespace Tests\Mock\FindQueries;

use Illuminate\Database\Query\Builder;
use Tests\Mock\FindBaseQuery;

class FindFiltersQuery extends FindBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "first_name",
        "last_name",
    ];

    protected array $rules = [
        'id' => 'nullable|integer',
    ];

    protected function before(Builder $sql): void
    {
        if ($this->hasInput('id')) {
            $sql->where('id', $this->id);
        }
    }
}
