<?php

namespace D2\ApiQuery;

use D2\ApiQuery\Components\Fields;
use D2\ApiQuery\Components\FieldsTrait;
use D2\ApiQuery\Contracts\FormatterContract;
use D2\ApiQuery\Contracts\RelationContract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Validation\ValidationException;
use PhpParser\Node\Expr\Instanceof_;

abstract class ItemQuery
{
    use FieldsTrait;

    protected string  $sqlConnection;
    protected ?string $table = null;
    protected string  $primaryKey;
    protected array   $allowedFields = [];

    protected Builder $sql;
    protected Fields  $fields;
    protected array   $input;

    private $key;

    protected abstract function validator(array $input, array $rules): Validator;

    protected abstract function formatter(): FormatterContract;

    public function __construct($key, array $input, ...$params)
    {
        $validator = $this->validator($input, [
            'fields' => 'nullable|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->boot($validator->validated());
        $this->findItem($key);
    }

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
        $this->registerSqlFields();
    }

    private function registerSqlFields(): void
    {
        if ($this->table) {
            $this->sql = Capsule::connection($this->sqlConnection)->table($this->table);
            $this->sql->select(
                $this->fields->toSql($this->table)
            );
        }

        else {
            $this->sql = Capsule::connection($this->sqlConnection);
            $this->sql->select(
                $this->fields->toSql()
            );
        }
    }

    private function findItem($key): void
    {
        $prefix = $this->table ? "{$this->table}." : "";

        $this->sql->where($prefix.$this->primaryKey, $key);
    }

    /**
     * @return object
     */
    public function results()
    {
        $query  = $this->sql;
        $fields = $this->fields;

        $this->before($query);

        if (($item = $query->first())) {
            $this->makeItemAdditions($item, $this->additionMethods());
            $this->makeItemFormats($item, $fields->formats());
            $this->after($item);
            $this->makeItemRelations($item, $this->relationMethods());
            $this->makeItemHiddens($item, $fields->hidden());
        }

        return $item;
    }

    protected function makeItemAdditions($item, array $methods): void
    {
        foreach ($methods as $field => $method) {
            $item->$field = $this->$method($item);
        }
    }

    protected function makeItemFormats($item, array $formatters): void
    {
        foreach ($formatters as $field => $method) {
            $item->$field = $this->formatter()->format($method, $item->$field);
        }
    }

    private function makeItemRelations($item, array $relations): void
    {
        foreach ($relations as $field => $method) {
            $this->$method($item)->to($item, $field);
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

    public function fields(): Fields
    {
        return $this->fields;
    }

    protected function before(Builder $sql): void
    {

    }

    protected function after($results): void
    {

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
