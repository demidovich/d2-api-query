<?php

namespace Tests\Mock\FindQueries;

use D2\ApiQuery\Relations\CollectionHasOne;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Tests\Mock\FindBaseQuery;

class FindPersonRelationQuery extends FindBaseQuery
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
        $ids = $this->collectionField($results, "city_id");

        $relatedData = $this->sqlTable("city")->select([
            "id",
            "name"
        ])->whereIn("id", $ids)->get();

        return new CollectionHasOne($relatedData, "city_id", "id");
    }

    /**
     * @property Collection|Paginator $results
     */
    private function collectionField($results, $field): array
    {
        $values = $results->pluck($field)->toArray();

        return array_unique($values);
    }
}
