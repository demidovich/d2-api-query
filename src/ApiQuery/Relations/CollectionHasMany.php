<?php

namespace D2\ApiQuery\Relations;

class CollectionHasMany extends CollectionHasOne
{
    protected function relatedDataByKey($relatedData)
    {
        $relationKey = $this->relationKey;
        $results = [];

        foreach ($relatedData as $row) {
            if (empty($row->$relationKey)) {
                continue;
            }
            $results[$row->$relationKey][] = $row;
        }

        return $results;
    }

    protected function nullableRelation($item, $field): void
    {
        $item->$field = [];
    }
}
