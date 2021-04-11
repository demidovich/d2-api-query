<?php

namespace D2\ApiQuery;

class Fields
{
    private array $fields = [];
    private array $dependencies = [];

    public function __construct(array $fields, array $dependencies)
    {
        foreach ($fields as $k => $v) {
            if (is_int($k)) {
                $field    = $v;
                $describe = null;
            } else {
                $field    = $k;
                $describe = $v;
            }
            $this->addField($field, $describe);
        }

        foreach ($dependencies as $field => $d) {

        }
    }

    private function addField(string $field, ?string $describe = null): void
    {

    }

    private function addDependency(string $field, array $dependencyFields): void
    {

    }

    public function toSql(): array
    {

    }

    public function formatResults(object $row): object
    {

    }
}
