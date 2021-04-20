<?php

namespace D2\ApiQuery\Relations;

use D2\ApiQuery\Contracts\RelationContract;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

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
            $results->$field = $this->relatedData;
            return;
        }

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
        $relationKey = $this->relationKey;
        $results = [];

        foreach ($relatedData as $row) {
            if (empty($row->$relationKey)) {
                continue;
            }
            if (isset($results[$row->$relationKey])) {
                continue;
            }
            $results[$row->$relationKey] = $row;
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
