<?php

namespace D2\ApiQuery;

use D2\ApiQuery\Components\Fields;
use D2\ApiQuery\Components\FieldsTrait;
use D2\ApiQuery\Contracts\FormatterContract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Validation\ValidationException;

abstract class ItemQuery
{
    use FieldsTrait;

    protected string  $sqlConnection;
    protected string  $table;
    protected string  $primaryKey;
    protected array   $allowedFields = [];

    protected Builder $sql;
    protected Fields  $fields;
    protected array   $input;

    private $key;

    protected abstract function validator(array $input, array $rules): Validator;

    protected abstract function formatter(): FormatterContract;

    protected function boot(array $input): void
    {
        $this->sql   = Capsule::connection($this->sqlConnection)->table($this->table);
        $this->input = $input;

        $this->fields = $this->fieldsInstance(
            $this->allowedFields, 
            $this->formatter(),
            $this->input
        );
    }

    public function __construct($key, array $input, ...$params)
    {
        $validator = $this->validator($input, [
            'fields' => 'nullable|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->boot(
            $validator->validated()
        );

        $this->key = $key;
    }

    /**
     * @return object
     */
    public function results()
    {
        $query  = $this->sql;
        $fields = $this->fields;

        $query->select($fields->sql());
        $query->where("{$this->table}.{$this->primaryKey}", $this->key);

        $this->before($query);

        if (($item = $query->first())) {
            $this->makeItemAdditions($item, $additionMethods);
            $this->makeItemFormats($item, $fields->formats());
            $this->after($item);
          //$this->makeItemRelations($results, $fields->relations());
            $this->makeItemHiddens($item, $fields->hidden());
        }

        return $item;
    }

    protected function makeItemAdditions($item, array $additionMethods): void
    {
        foreach ($additionMethods as $newField => $method) {
            $item->$newField = $this->$method($item);
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

    public function sql(): Builder
    {
        return $this->sql;
    }

    protected function before(Builder $sql): void
    {

    }

    protected function after($results): void
    {

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
