<?php

namespace D2\ApiQuery\Relations;

use D2\ApiQuery\Contracts\RelationContract;

class ItemRelation implements RelationContract
{
    private $relatedData;

    public function __construct($relatedData)
    {
        $this->relatedData = $relatedData;
    }

    public function to($results, string $field): void
    {
        $results->$field = $this->relatedData;
    }
}
