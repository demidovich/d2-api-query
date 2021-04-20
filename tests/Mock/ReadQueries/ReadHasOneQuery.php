<?php

namespace Tests\Mock\ReadQueries;

use D2\ApiQuery\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Tests\Mock\ReadBaseQuery;

class ReadHasOneQuery extends ReadBaseQuery
{
    protected ?string $table = "person";
    protected  string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "city" => "relation|depends:city_id"
    ];

    protected function cityRelation($results): HasOne
    {
        $relatedData = null;

        if (isset($results->city_id)) {
            $relatedData = $this->sqlTable("city")->select([
                "id",
                "name"
            ])->where("id", $results->city_id)->first();
        }

        return new HasOne($relatedData, "city_id", "id");
    }

    protected function before(Builder $sql): void
    {
        $sql->orderBy("id");
    }
}
