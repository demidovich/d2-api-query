<?php

namespace D2\ApiQuery;

use D2\ApiQuery\Components\Fields;
use D2\ApiQuery\Contracts\FormatterContract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

abstract class ItemQuery2
{
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

    public function __construct($key, array $input, ...$params)
    {
        $validator = $this->validator($input, [
            'fields' => 'nullable|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->input = $validator->validated();

        $this->fields = $this->fieldsInstance(
            $this->allowedFields, 
            $this->input
        );

        $this->sql = Capsule::connection($this->sqlConnection)->table($this->table);
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

        $item = $query->first();

        if ($item) {
            $appendMethods = $this->existingAppendMethods($fields);
            $this->makeItemAppends($item, $appendMethods);

            $this->ensureExistenceFormatters($fields);
            $this->makeItemFormats($item, $fields->formats());

            //$this->makeItemRelations($results, $fields->relations());

            $this->after($item);
            $this->makeItemHiddens($item, $fields->hidden());
        }

        return $item;
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

    protected function ensureExistenceFormatters(Fields $fields): void
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

    private function camelCase(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return lcfirst(str_replace(' ', '', $value));
    }

    private function fieldsInstance(array $allowedRaw, array $input): Fields
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

        return $fields;
    }
}
