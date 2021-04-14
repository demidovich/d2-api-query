<?php

namespace Tests\Mock;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

class FindPersonAppendsJoinQuery extends BaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "city" => "append",
    ];

    protected function before(Builder $sql): void
    {
        $this->joinCity($sql);
    }

    protected function cityAppend(object $row)
    {
        if (isset($row->city_id)) {
            return [
                "id" => $row->city_id,
                "name" => $row->city_name,
            ];
        }

        return null;
    }

    private function joinCity(Builder $sql): void
    {
        if (! $this->hasRequestedField("city")) {
            return;
        }

        $sql->leftJoin("city", function (JoinClause $join) {
            $join->on("city.id", "=", "person.city_id");
        });

        $sql->addSelect([
            "city.id as city_id",
            "city.name as city_name",
        ]);

        $this->fields()->hide([
            "city_id", 
            "city_name",
        ]);
    }
}
