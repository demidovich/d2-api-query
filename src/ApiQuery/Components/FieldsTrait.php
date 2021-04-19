<?php

namespace D2\ApiQuery\Components;

use D2\ApiQuery\Contracts\FormatterContract;
use D2\ApiQuery\Contracts\RelationContract;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

trait FieldsTrait
{
    private array $additionMethods = [];
    private array $relationMethods = [];

    private function fieldsInstance(array $allowedRaw, FormatterContract $formatter, array $input): Fields
    {
        $fields = new Fields();

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

    protected function initAdditions(Fields $fields): void
    {
        foreach ($fields->additions() as $addition) {
            $method = $this->camelCase($addition) . 'Addition';
            if (! method_exists($this, $method)) {
                $class = get_called_class();
                throw new RuntimeException("В $class отсутствует addition метод $method");
            }
            $this->additionMethods[$addition] = $method;
        }
    }

    protected function initRelations(Fields $fields): void
    {
        $relations = $fields->relations();
        if (! $relations) {
            return;
        }

        $class = new ReflectionClass($this);

        foreach ($fields->relations() as $relation) {

            $methodName = $this->camelCase($relation) . 'Relation';
            if (! $class->hasMethod($methodName)) {
                throw new RuntimeException("В {$class->getName()} отсутствует relation метод $methodName");
            }

            $method = $class->getMethod($methodName);
            if (!  $method->hasReturnType()
                || $method->getReturnType()->allowsNull()
                || ! class_implements($method->getReturnType()->getName(), RelationContract::class))
            {
                throw new RuntimeException(
                    "В {$class->getName()} метод $methodName не декларирует возвращаемый тип, реализующий " . RelationContract::class
                );
            }

            $this->relationMethods[$relation] = $method->getName();
        }
    }

    protected function additionMethods(): array
    {
        return $this->additionMethods;
    }

    protected function relationMethods(): array
    {
        return $this->relationMethods;
    }

    private function camelCase(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return lcfirst(str_replace(' ', '', $value));
    }
}
