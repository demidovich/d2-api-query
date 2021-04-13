<?php

namespace Tests\Mock;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

class FindPersonRelationJoinQuery extends BaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "city" => "append",
    ];

    protected function before(Builder $sql): void
    {
        if ($this->hasRequestedField("city")) {
            $this->joinCity($sql);
        }
    }

    protected function cityAppend(object $row)
    {
        $row->city = [
            "id" => $row->city_id,
            "name" => $row->city_name,
        ];
    }

    private function joinCity(Builder $sql): void
    {
        $sql->join("city", function (JoinClause $join) {
            $join->where("city.id", "person.city_id");
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
