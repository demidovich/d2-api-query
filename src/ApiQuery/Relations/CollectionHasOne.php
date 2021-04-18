<?php

namespace D2\ApiQuery\Relations;

use ArrayIterator;
use D2\ApiQuery\Contracts\RelationContract;
use Illuminate\Support\Collection;
use RuntimeException;

class CollectionHasOne implements RelationContract
{
    private $relatedData;
    private $localKey;
    private $relationKey;

    /**
     * @property Collection|array $relatedData
     * @property string|integer $localKey
     * @property string|integer $relationKey
     */
    public function __construct($relatedData, $localKey, $relationKey)
    {
        $this->localKey    = $localKey;
        $this->relationKey = $relationKey;
        $this->relatedData = $this->relatedDataByKey($relatedData);
    }

    public function to($results, string $field): void
    {
        $lokalKeyName = $this->localKey;
        $relatedByKey = $this->relatedData;

        foreach ($results as $item) {
           
            if (! isset($item->$lokalKeyName)) {
                $this->nullableRelation($item, $field);
                continue;
            }

            $key = $item->$lokalKeyName;

            if (! isset($relatedByKey[$key])) {
                $this->nullableRelation($item, $field);
                continue;
            }

            $item->$field = $relatedByKey[$key];
        }
    }

    private function relatedDataByKey($relatedData)
    {
        $relationKey = $this->relationKey;

        if ($relatedData instanceof Collection) {
            return $relatedData->whereNotNull($relationKey)->keyBy($relationKey);
        }

        if (is_array($relatedData) || $relatedData instanceof ArrayIterator) {
            $results = [];
            foreach ($relatedData as $row) {
                if (empty($row->$relationKey)) {
                    continue;
                }
                $results[$row->$relationKey] = $row;
            }
            return $results;
        }

        throw new RuntimeException("Некорректниый тип relatedData.");
    }

    private function nullableRelation($item, $field): void
    {
        $item->$field = null;
    }
}
