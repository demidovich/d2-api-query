<?php

namespace D2\ApiQuery\Components;

use D2\ApiQuery\Contracts\FormatterContract;
use D2\ApiQuery\Contracts\RelationContract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

abstract class BaseQuery
{
    use FieldsTrait;

    protected Builder $sql;
    protected Fields  $fields;
    protected array   $input;

    protected abstract function validator(array $input, array $rules): Validator;

    protected abstract function formatter(): FormatterContract;

    protected function boot(array $input): void
    {
        $this->input = $input;

        $this->fields = $this->fieldsInstance(
            $this->allowedFields, 
            $this->formatter(),
            $this->input
        );

        $this->registerAdditions($this->fields);
        $this->registerRelations($this->fields);

        $this->sql = Capsule::connection($this->sqlConnection)->table($this->table);
        $this->sql->select(
            $this->fields->toSql($this->table)
        );
    }

    protected function registerAdditions(Fields $fields): void
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

    protected function registerRelations(Fields $fields): void
    {
        if (! $fields->relations()) {
            return;
        }

        $class = new ReflectionClass($this);

        foreach ($fields->relations() as $relation) {
            $method = $this->camelCase($relation) . 'Relation';
            $this->ensureCorrectRelation($class, $method);
            $this->relationMethods[$relation] = $method;
        }
    }

    private function ensureCorrectRelation(ReflectionClass $class, string $methodName): void
    {
        if (! $class->hasMethod($methodName)) {
            throw new RuntimeException(
                vsprintf('В %s отсутствует relation метод %s', [
                    $class->getName(),
                    $methodName
                ])
            );
        }

        /** @var ReflectionNamedType $type */
        $type = $class->getMethod($methodName)->getReturnType();

        if (!  $type
            || $type->allowsNull()
            || $type->isBuiltin()
            || ! is_subclass_of($type->getName(), RelationContract::class)
        ) {
            throw new RuntimeException(
                vsprintf('В %s метод %s не декларирует возвращаемый тип, реализующий %s.', [
                    $class->getName(),
                    $methodName,
                    RelationContract::class
                ])
            );
        }
    }

    public function sql(): Builder
    {
        return $this->sql;
    }

    public function fields(): Fields
    {
        return $this->fields;
    }

    protected function makeItemAdditions(object $item, array $methods): void
    {
        foreach ($methods as $field => $method) {
            $item->$field = $this->$method($item);
        }
    }

    protected function makeItemFormats(object $item, array $formatters): void
    {
        foreach ($formatters as $field => $method) {
            $item->$field = $this->formatter()->format($method, $item->$field);
        }
    }

    protected function makeItemHiddens(object $item, array $fields): void
    {
        foreach ($fields as $field) {
            unset($item->$field);
        }
    }

    /**
     * Были ли запрошено поле в параметре запроса fields.
     */
    protected function hasRequestedField(string $name): bool
    {
        return $this->fields->has($name);
    }

    protected function paramException(string $param, string $message): void
    {
        $validator = $this->validator([], []);
        $validator->getMessageBag()->add($param, $message);

        throw new ValidationException($validator);
    }

    public function __get($name)
    {
        return $this->input[$name];
    }

    public function __isset($name)
    {
        return isset($this->input[$name]);
    }
}
