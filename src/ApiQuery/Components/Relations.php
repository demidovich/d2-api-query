<?php

namespace D2\ApiQuery\Components;

class Relations
{
    private Fields $fields;
    private array  $relations = [];

    public function __construct(Fields $fields, array $relations)
    {
        $this->fields = $fields;

        foreach ($relations as $k => $v) {
            if (is_int($k)) {
                $relation = $v;
                $config   = null;
            } else {
                $relation = $k;
                $config   = $v;
            }
            $this->add($relation, $config);
        }
    }

    public static function empty(): self
    {
        $fields = new Fields([]);

        return new self($fields, []);
    }

    private function add(string $relation, string $config): void
    {

    }

    public function all(): array
    {
        return $this->relations;
    }
}
