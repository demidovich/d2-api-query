<?php

namespace D2\ApiQuery;

use D2\ApiQuery\Components\BaseQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\ValidationException;

abstract class ItemQuery extends BaseQuery
{
    protected string  $table;
    protected string  $primaryKey;
    protected array   $allowedFields = [];

    public function __construct($key, array $input, ...$params)
    {
        $validator = $this->validator($input, [
            'fields' => 'nullable|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->boot($validator->validated());
        $this->sqlFind($key);
    }

    private function sqlFind($key): void
    {
        $prefix = $this->table ? "{$this->table}." : "";

        $this->sql->where($prefix.$this->primaryKey, $key);
    }

    /**
     * @return null|object
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

    private function makeItemRelations(object $item, array $relations): void
    {
        foreach ($relations as $field => $method) {
            $relation = $this->$method($item);
            $relation->to($item, $field);
        }
    }

    protected function before(Builder $sql): void
    {

    }

    protected function after(object $results): void
    {

    }
}
