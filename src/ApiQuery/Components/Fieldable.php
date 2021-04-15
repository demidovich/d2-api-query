<?php

namespace D2\ApiQuery\Components;

trait Fieldable
{
    private Fields $fields;

    public function fields(): Fields
    {
        return $this->fields;
    }

    protected function hasRequestedField(string $name): bool
    {
        return $this->fields->has($name);
    }

    protected function initFields(array $allowedRaw, array $input): void
    {
        $fields = new Fields($this->table);

        $allowed = [];
        foreach ($allowedRaw as $k => $v) {
            if (is_int($k)) {
                $k = $v;
                $v = null;
            }
            $allowed[$k] = $v;
        }

        if (! isset($input['fields'])) {
            foreach ($allowed as $field => $config) {
                $fields->add($field, $config);
            }
            $this->fields = $fields;
            return;
        }

        $requested = array_unique(explode(',', $input['fields']));
        $prepared  = [];
        $denied    = [];

        foreach ($requested as $field) {
            if (array_key_exists($field, $allowed)) {
                $prepared[$field] = $allowed[$field];
            } else {
                $denied[] = $field;
            }
        }

        if ($denied) {
            $this->paramException(
                "fields",
                sprintf('Поля "%s" отсутствуют в списке разрешенных.', implode('", "', $denied))
            );
        }

        foreach ($prepared as $field => $config) {
            $fields->add($field, $config);
        }

        $this->fields = $fields;
    }
}
