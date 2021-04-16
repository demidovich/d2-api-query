<?php

namespace D2\ApiQuery\Components;

use D2\ApiQuery\Contracts\FormatterContract;
use RuntimeException;

trait FieldsTrait
{
    private function fieldsInstance(array $allowedRaw, FormatterContract $formatter, array $input): Fields
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
            return $fields;
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

        $this->ensureCorrectFormatters($fields, $formatter);
        $this->ensureCorrectAdditions($fields);

        return $fields;
    }

    private function ensureCorrectFormatters(Fields $fields, FormatterContract $formatter): void
    {
        $formatters = $fields->formats();

        if ($formatters) {
            foreach ($formatters as $field => $method) {
                if (! $formatter->has($method)) {
                    $class = get_called_class();
                    throw new RuntimeException("В $class для поля $field указан несуществующий format метод $method."); 
                }
            }
        }
    }

    private function ensureCorrectAdditions(Fields $fields): void
    {
        foreach ($fields->additions() as $addition) {
            $method = $this->additionMethod($addition);
            if (! method_exists($this, $method)) {
                $class = get_called_class();
                throw new RuntimeException("В $class отсутствует addition метод $method");
            }
        }
    }

    protected function additionMethod(string $name): string
    {
        return $this->camelCase($name) . 'Addition';
    }

    private function camelCase(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return lcfirst(str_replace(' ', '', $value));
    }
}
