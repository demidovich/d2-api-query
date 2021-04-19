<?php

namespace Tests\Mock\FindQueries;

use D2\ApiQuery\Relations\CollectionHasMany;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Tests\Mock\FindBaseQuery;

class FindPersonHasManyQuery extends FindBaseQuery
{
    protected ?string $table = "person";

    protected array $allowedFields = [
        "id",
        "horse" => "relation|depends:id",
    ];

    /**
     * @property Collection|Paginator
     */
    protected function horseRelation($results): CollectionHasMany
    {
        $ids = $this->pluckUnique($results, "id");

        $relatedData = $this->sqlTable("horse")->select([
            "id",
            "name",
            "person_id"
        ])->orderBy("name")->whereIn("person_id", $ids)->get();

        return new CollectionHasMany($relatedData, "id", "person_id");
    }
}
