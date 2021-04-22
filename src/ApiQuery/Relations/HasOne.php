<?php

namespace D2\ApiQuery\Relations;

use D2\ApiQuery\Contracts\RelationContract;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use RuntimeException;

class HasOne implements RelationContract
{
    protected $relatedData;
    protected $localKey;
    protected $relationKey;
    protected bool $belongsToCollection;

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
        if ($this->isItem($results)) {
            $this->toItem($results, $field);
        } else {
            $this->toCollection($results, $field);
        }
    }

    private function toItem($results, string $field): void
    {
        $lokalKeyName = $this->localKey;
        $relatedByKey = $this->relatedData;
        $key = $results->$lokalKeyName;

        if (isset($relatedByKey[$key])) {
            $results->$field = $relatedByKey[$key];
        } else {
            $this->nullableRelation($results, $field);
        }
    }

    private function toCollection($results, string $field): void
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

    protected function relatedDataByKey($relatedData)
    {
        $results = [];
        $relationKey = $this->relationKey;

        if (! $relatedData) {
            return $results;
        }

        $first = $this->isItem($relatedData) ? $relatedData : $relatedData[0];

        if (! isset($first->$relationKey)) {
            throw new RuntimeException("В relatedData отсутствует поле внешнего ключа.");
        }

        if ($this->isItem($relatedData)) {
            $results[$relatedData->$relationKey] = $relatedData;
        } else {
            foreach ($relatedData as $row) {
                $results[$row->$relationKey] = $row;
            }
        }

        return $results;
    }

    protected function nullableRelation($item, $field): void
    {
        $item->$field = null;
    }

    protected function isItem($results): bool
    {
        return ! $this->isCollection($results);
    }

    protected function isCollection($results): bool
    {
        return $results instanceof Collection 
            || $results instanceof Paginator 
            || is_array($results);
    }
}
