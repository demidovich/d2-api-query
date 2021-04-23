<?php

namespace Tests\Mock\ReadQueries;

use D2\ApiQuery\Relations\HasOnePrefix;
use Illuminate\Database\Query\Builder;
use Tests\Mock\ReadBaseQuery;

class ReadHasOnePrefixQuery extends ReadBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "city" => "relation|depends:city_id"
    ];

    protected function before(Builder $sql): void
    {
        if ($this->hasRequestedField("city")) {
            $sql
                ->leftJoin("city", "city.id", "=", "person.city_id")
                ->addSelect([
                    "city.id as city_id",
                    "city.name as city_name",
                ]);
        }
    }

    protected function cityRelation($results): HasOnePrefix
    {
        return new HasOnePrefix($results, "city");
    }
}
