<?php

namespace D2\ApiQuery;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * fields=id,name
 * with[city]=id,name
 * with[]=city
 * 
 * $allowedFields = [
 *     'id',
 *     'created_at' => 'sql:to_json(created_at)'
 * ]
 */
abstract class CollectionQuery extends ItemQuery
{
    protected array $rules    = [];
    protected int   $maxCount = 1000;
    protected int   $perPage  = 25;

    public function __construct(array $input, ...$params)
    {
        $rules = [
            'fields' => 'nullable|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/',
          //'with'   => 'nullable|array',
          //'with.*' => 'required|regex:/^[a-z\d_]+$/',
          //'with.*' => 'required|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/', // with[relation]=field1,field2
            'sort'   => 'nullable|array',
            'sort.*' => 'in:asc,desc',
            'count'  => 'nullable|int',
            'page'   => 'nullable|int',
        ] + $this->rules();

        $validator = $this->validator($input, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->boot($input);
    }

    /**
     * @return Collection|Paginator
     */
    public function results()
    {
        $sql = $this->sql();

        $this->before($sql);

        $collection = $this->limitedCollection($sql);

        if ($collection->count() > 0) {
            $this->makeCollectionAdditions($collection);
            $this->makeCollectionFormats($collection);
            $this->makeCollectionRelations($collection);
            $this->after($collection);
            $this->makeCollectionHiddens($collection);
        }

        return $collection;
    }

    /**
     * @return Collection|Paginator
     */
    private function limitedCollection(Builder $sql)
    {
        $input = $this->input;

        if (! array_key_exists('count', $input)) {
            return $sql->simplePaginate($this->perPage)->appends($input);
        }

        $count = (int) $input['count'];

        if ($count < 1) {
            $count = $this->maxCount;
        }

        if ($count > $this->maxCount) {
            $this->paramException(
                "count",
                "Превышено максимально допустимое значение count $this->maxCount."
            );
        }

        return $sql->limit($count)->get();
    }

    public function setMaxCount(int $value): void
    {
        $this->maxCount = $value;
    }

    /**
     * Наличие во входных данных поля, описанного в rules.
     *
     * Метод полезен в одном случае: когда описано поле bool и нужно сделать
     * проверку, что этот фильтр в запросе существует и он имеет значение false.
     * В этом случает проверка if ($this->filter) в любом случае завершится
     * неудачей.
     */
    protected function hasInput(string $name): bool
    {
        return array_key_exists($name, $this->input);
    }

    /**
     * @property Collection|Paginator
     */
    protected function makeCollectionAdditions($results): void
    {
        $additions = $this->additionMethods();

        if (! $additions) {
            return;
        }

        foreach ($results as $row) {
            foreach ($additions as $additionField => $method) {
                $row->$additionField = $this->$method($row);
            }
        }
    }

    /**
     * @property Collection|Paginator
     */
    protected function makeCollectionFormats($results): void
    {
        $formatFields = $this->fields->formats();

        if (! $formatFields) {
            return;
        }

        foreach ($results as $item) {
            $this->makeItemFormats($item, $formatFields);
        }
    }

    /**
     * @property Collection|Paginator
     */
    protected function makeCollectionRelations($results): void
    {
        $methods = $this->relationMethods();

        if (! $methods) {
            return;
        }

        foreach ($methods as $field => $method) {
            $relation = $this->$method($results);
            $relation->to($results, $field);
        }
    }

    /**
     * @property Collection|Paginator
     */
    protected function makeCollectionHiddens($results): void
    {
        $hidden = $this->fields->hidden();

        if (! $hidden) {
            return;
        }

        foreach ($results as $item) {
            $this->makeItemHiddens($item, $hidden);
        }
    }

    /**
     * @property Collection|Paginator
     */
    protected function pluckUnique($collection, $field): array
    {
        return $collection
            ->whereNotNull($field)
            ->pluck($field)
            ->unique()
            ->toArray();
    }

    protected function rules(): array
    {
        return $this->rules;
    }
}
