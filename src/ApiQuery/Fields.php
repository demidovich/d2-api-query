<?php

namespace D2\ApiQuery;

class Fields
{
    private array $allowedFields = [];
    private array $enabledFields = [];
    private array $dependencies  = [];

    public function __construct(array $fields)
    {
        foreach ($fields as $k => $v) {
            if (is_int($k)) {
                $field   = $v;
                $options = null;
            } else {
                $field   = $k;
                $options = $v;
            }
            $this->add($field, $options);
        }
    }

    private function add(string $field, ?string $options = null): void
    {

    }

    public function addDependency(string $field): void
    {
        if (! in_array($field, $this->dependencies)) {
            $this->dependencies[] = $field;
        }
    }

    public function hidden(): array
    {

    }

    public function toSql(): array
    {

    }

    public function formatResults(object $row): object
    {

    }

    public function allowed(string $field): bool
    {
        return array_key_exists($field, $this->allowedFields);
    }

    public function enable(string $field): void
    {
        if (! in_array($field, $this->enabledFields)) {
            $this->enabledFields = $field;
        }
    }

    public function enabled(string $field): bool
    {
        return array_key_exists($field, $this->enabledFields);
    }

    public function enableAll(): void
    {
        $this->enabledFields = array_keys($this->allowedFields);
    }
}
