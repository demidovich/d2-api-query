<?php

namespace Tests\Mock\FindQueries;

use D2\ApiQuery\Relations\HasOne;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Tests\Mock\FindBaseQuery;

class FindHasOneQuery extends FindBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";


    protected array $allowedFields = [
        "id",
        "city" => "relation|depends:city_id"
    ];

    /**
     * @property Collection|Paginator
     */
    protected function cityRelation($results): HasOne
    {
        $ids = $this->pluckUnique($results, "city_id");

        $relatedData = $this->sqlTable("city")->select([
            "id",
            "name"
        ])->whereIn("id", $ids)->get();

        return new HasOne($relatedData, "city_id", "id");
    }

    protected function before(Builder $sql): void
    {
        $sql->orderBy("id");
    }
}
