<?php

namespace Tests\Mock\FindQueries;

use D2\ApiQuery\Relations\CollectionHasOne;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Tests\Mock\FindBaseQuery;

class FindPersonHasOneQuery extends FindBaseQuery
{
    protected ?string $table = "person";

    protected array $allowedFields = [
        "id",
        "city" => "relation|depends:city_id"
    ];

    /**
     * @property Collection|Paginator
     */
    protected function cityRelation($results): CollectionHasOne
    {
        $ids = $this->pluckUnique($results, "city_id");

        $relatedData = $this->sqlTable("city")->select([
            "id",
            "name"
        ])->whereIn("id", $ids)->get();

        return new CollectionHasOne($relatedData, "city_id", "id");
    }

    protected function before(Builder $sql): void
    {
        $sql->orderBy("id");
    }
}
