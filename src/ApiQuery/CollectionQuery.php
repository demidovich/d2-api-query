<?php

namespace D2\ApiQuery;

use D2\ApiQuery\Components\BaseQuery;
use D2\ApiQuery\Components\SortTrait;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\Paginator;
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
abstract class CollectionQuery extends BaseQuery
{
    use SortTrait;

    protected string  $sqlConnection;
    protected string  $table;
    protected string  $primaryKey;
    protected array   $allowedFields = [];

    protected array   $rules    = [];
    protected int     $maxCount = 1000;
    protected int     $perPage  = 25;

    public function __construct(array $input, ...$params)
    {
        $rules = [
            'fields' => 'nullable|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/',
          //'with'   => 'nullable|array',
          //'with.*' => 'required|regex:/^[a-z\d_]+$/',
          //'with.*' => 'required|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/', // with[relation]=field1,field2
            'count'  => 'nullable|int',
            'page'   => 'nullable|int',
            'sort.*' => 'required|in:asc,desc',
            'sort'   => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    foreach ($value as $field => $direction) {
                        if (! preg_match("/^[a-z_]{1}[a-z\d_]*$/i", $field)) {
                            $fail("The $attribute has invalid field name $field.");
                        }
                    }
                }
            ]
        ] + $this->rules();

        $validator = $this->validator($input, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->boot($input);
    }

    /**
     * @return Paginator&static|Collection
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

    private function limitedCollection(Builder $sql)
    {
        $input = $this->input;

        if (! array_key_exists('count', $input)) {
            $results = $sql->simplePaginate($this->perPage)->appends($input);
            return $results;
        }

        $count = (int) $input['count'];

        if ($count < 1) {
            $count = $this->maxCount;
        }

        if ($count > $this->maxCount) {
            $this->paramException(
                "count",
                "?????????????????? ?????????????????????? ???????????????????? ???????????????? count $this->maxCount."
            );
        }

        return $sql->limit($count)->get();
    }

    public function setMaxCount(int $value): void
    {
        $this->maxCount = $value;
    }

    /**
     * ?????????????? ???? ?????????????? ???????????? ????????, ???????????????????? ?? rules.
     *
     * ?????????? ?????????????? ?? ?????????? ????????????: ?????????? ?????????????? ???????? bool ?? ?????????? ??????????????
     * ????????????????, ?????? ???????? ???????????? ?? ?????????????? ???????????????????? ?? ???? ?????????? ???????????????? false.
     * ?? ???????? ?????????????? ???????????????? if ($this->filter) ?? ?????????? ???????????? ????????????????????
     * ????????????????.
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
        $hiddenFields = $this->fields->hidden($results[0]);

        if (! $hiddenFields) {
            return;
        }

        foreach ($results as $item) {
            $this->makeItemHiddens($item, $hiddenFields);
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

    protected function before(Builder $sql): void
    {

    }

    /**
     * @property Collection|Paginator
     */
    protected function after($results): void
    {

    }
}
