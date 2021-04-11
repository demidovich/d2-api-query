<?php

namespace D2\ApiQuery;

class Relations
{
    private array $allowedRelations = [];
    private array $enabledRelations = [];

    public function __construct(array $relations)
    {
        foreach ($relations as $k => $v) {
            if (is_int($k)) {
                $relation = $v;
                $fields   = [];
            } else {
                $relation = $k;
                $fields   = $v;
            }
            $this->add($relation, $fields);
        }
    }

    private function add(string $relation, array $fields): void
    {
        $this->allowedRelations[$relation] = $fields;
    }

    public function allowed(string $relation): bool
    {
        return array_key_exists($relation, $this->allowedRelations);
    }

    public function enable(string $relation, array $fields): void
    {
        if (! in_array($relation, $this->enabledRelations)) {
            $this->enabledRelations[$relation] = $fields;
        }
    }

    public function enableAll(): void
    {
        $this->enabledRelations = array_keys($this->allowedRelations);
    }
}
