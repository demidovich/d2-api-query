<?php

namespace D2\ApiQuery\Relations;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use RuntimeException;

class HasOne
{
    private  string $queryClass;
    private  string $localKey;
    private  string $relationKey;
    private ?string $fields = null;
    private ?int    $maxCount = null;

    public function __construct(string $queryClass)
    {
        $this->queryClass = $queryClass;        
    }

    public function setLocalKey(string $field): self
    {
        $this->localKey = $field;
        return $this;
    }

    public function setRelationKey(string $field): self
    {
        $this->relationKey = $field;
        return $this;
    }

    public function setFields(string $fields): self
    {
        $this->relationKey = $fields;
        return $this;
    }

    public function setMaxCount(int $value): self
    {
        $this->maxCount = $value;
        return $this;
    }

    /**
     * @property Collection|Paginator
     */
    public function to($results)
    {
        $queryClass  = $this->queryClass;
        $localKey    = $this->localKey();
        $relationKey = $this->relationKey();

        $ids = $results->pluck($localKey)->toArray();

        $query = $queryClass::fromArray([
            'ids'    => $ids,
            'count'  => 0,
            'fields' => $this->fields
        ]);

        if ($this->maxCount) {
            $query->setMaxCount($this->maxCount);
        }

        $relationItems = $query->resultsBy($relationKey);

        foreach ($results as $row) {
            $row->city = (isset($row->city_id) && isset($cities[$row->city_id])) ? $cities[$row->city_id] : null;
        }

    }

    private function localKey()
    {
        if (property_exists($this, 'localKey')) {
            return $this->localKey;
        }

        throw new RuntimeException("Отсутствует параметр отношения localKey. Не был вызван setLocalKey().");
    }

    private function relationKey()
    {
        if (property_exists($this, 'relationKey')) {
            return $this->localKey;
        }

        throw new RuntimeException("Отсутствует параметр отношения relationKey. Не был вызван setRelationKey().");
    }
}
