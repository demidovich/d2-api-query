<?php

namespace Tests\Mock\Invalid;

use D2\ApiQuery\Relations\HasMany;
use D2\ApiQuery\Relations\HasOne;
use Tests\Mock\ReadBaseQuery;

class InvalidRelationQuery extends ReadBaseQuery
{
    protected string $table = "person";
    protected string $primaryKey = "id";

    protected array $allowedFields = [
        "id",
        "missing_method" => "relation",
        "bad_contract_method" => "relation",
        "missing_item_relation_key" => "relation",
        "missing_collection_relation_key" => "relation",
    ];

    protected function badContractMethodRelation($results)
    {

    }

    protected function missingItemRelationKeyRelation($results)
    {
        $relatedData = new \stdClass;
        $relatedData->name = "Name";

        return new HasOne($relatedData, "id", "relation_id");
    }

    protected function missingCollectionRelationKeyRelation($results)
    {
        $relatedData = new \stdClass;
        $relatedData->name = "Name";

        return new HasMany([$relatedData], "id", "relation_id");
    }
}
