<?php

namespace D2\ApiQuery\Relations;

class HasMany extends HasOne
{
    protected function relatedDataByKey($relatedData)
    {
        $results = [];
        $relationKey = $this->relationKey;

        foreach ($relatedData as $row) {
            if (! empty($row->$relationKey)) {
                $results[$row->$relationKey][] = $row;
            }
        }

        return $results;
    }

    protected function nullableRelation($item, $field): void
    {
        $item->$field = [];
    }
}
