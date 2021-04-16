<?php

namespace D2\ApiQuery\Components;

use RuntimeException;

trait ItemTrait
{
    protected function existingAppendMethods(Fields $fields): array
    {
        $methods = [];

        foreach ($fields->appends() as $append) {
            $method = $this->camelCase($append) . 'Append';
            if (! method_exists($this, $method)) {
                $class = get_called_class();
                throw new RuntimeException("В $class отсутствует append метод $method");
            }
            $methods[$append] = $method;
        }

        return $methods;
    }

    /**
     * @property object
     */
    protected function makeItemAppends($item, array $appendMethods): void
    {
        foreach ($appendMethods as $appendedField => $method) {
            $item->$appendedField = $this->$method($item);
        }
    }

    protected function ensureExistingFormatters(Fields $fields): void
    {
        if (! $fields->formats()) {
            return;
        }

        foreach ($fields->formats() as $field => $method) {
            if (! $this->formatter()->has($method)) {
                $class = get_called_class();
                throw new RuntimeException("В $class для поля $field указан несуществующий format метод $method."); 
            }
        }
    }

    protected function makeItemFormats($item, array $fieldMethods): void
    {
        foreach ($fieldMethods as $field => $method) {
            $item->$field = $this->formatter()->format($method, $item->$field);
        }
    }

    protected function makeItemHiddens($item, array $fields): void
    {
        foreach ($fields as $field) {
            unset($item->$field);
        }
    }

    protected function camelCase(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return lcfirst(str_replace(' ', '', $value));
    }
}