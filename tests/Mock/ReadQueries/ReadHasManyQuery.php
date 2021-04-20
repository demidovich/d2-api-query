<?php

namespace Tests\Mock\ReadQueries;

use D2\ApiQuery\Relations\HasMany;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Tests\Mock\ReadBaseQuery;

class ReadHasManyQuery extends ReadBaseQuery
{
    protected ?string $table = "person";
    protected  string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "horse" => "relation|depends:id",
    ];

    /**
     * @property Collection|Paginator
     */
    protected function horseRelation($results): HasMany
    {
        $relatedData = $this->sqlTable("horse")->select([
            "id",
            "name",
            "person_id"
        ])->orderBy("name")->where("person_id", $results->id)->get();

        return new HasMany($relatedData, "id", "person_id");
    }
}
