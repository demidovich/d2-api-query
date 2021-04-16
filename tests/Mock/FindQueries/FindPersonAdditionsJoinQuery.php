<?php

namespace Tests\Mock\FindQueries;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Tests\Mock\FindBaseQuery;

class FindPersonAdditionsJoinQuery extends FindBaseQuery
{
    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "city" => "addition",
    ];

    protected function before(Builder $sql): void
    {
        $this->joinCity($sql);
    }

    protected function cityAddition(object $row)
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
