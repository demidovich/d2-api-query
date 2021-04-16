<?php

namespace D2\ApiQuery;

use D2\ApiQuery\Components\Fields;
use D2\ApiQuery\Contracts\FormatterContract;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
abstract class CollectionQuery2 extends ItemQuery2
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

        $this->input = $validator->validated();
        $this->sql   = Capsule::connection($this->sqlConnection)->table($this->table);

        $this->fields = $this->fieldsInstance(
            $this->allowedFields, 
            $this->input
        );
    }

    protected function rules(): array
    {
        return $this->rules;
    }

    /**
     * @return Collection|Paginator
     */
    public function results()
    {
        $sql    = $this->sql();
        $fields = $this->fields;

        $sql->select($fields->sql());
        $this->before($sql);
        $results = $this->limitedResults($sql);

        if ($results->count() > 0) {
            $this->makeCollectionAppends($results, $fields->appends());
            $this->makeCollectionFormats($results, $fields->formats());
            $this->makeCollectionRelations($results, $fields->relations());
            $this->after($results);
            $this->makeCollectionHiddens($results, $fields->hidden());
        }

        return $results;
    }

    /**
     * @return Collection|Paginator
     */
    public function resultsBy(string $key)
    {
        return $this->results()->keyBy($key);
    }

    /**
     * @return Collection|Paginator
     */
    private function limitedResults(Builder $sql)
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

    public function sql(): Builder
    {
        return $this->sql;
    }

    public function fields(): Fields
    {
        return $this->fields;
    }

    public function setMaxCount(int $value): void
    {
        $this->maxCount = $value;
    }

    protected function before(Builder $sql): void
    {

    }

    /**
     * @property Collection|Paginator $results
     */
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

    /**
     * Наличие во входных данных поля, описанного в rules.
     *
     * Метод полезен в одном случае: когда описано поле bool и нужно сделать
     * проверку, что этот фильтр в запросе существует и он имеет значение false.
     * В этом случает проверка if ($this->filter) в любом случае завершится
     * неудачей.
     */
    protected function hasRequestedFilter(string $name): bool
    {
        return array_key_exists($name, $this->input);
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

    /**
     * @property Collection|Paginator
     */
    private function makeCollectionAppends($results, array $appends): void
    {
        if (! $appends) {
            return;
        }

        $methods = [];

        foreach ($appends as $name) {
            $method = $this->camelCase($name) . 'Append';
            if (! method_exists($this, $method)) {
                $class = get_called_class();
                throw new RuntimeException("В $class отсутствует append метод $method");
            }
            $methods[$name] = $method;
        }

        foreach ($results as $row) {
            foreach ($methods as $appendedField => $method) {
                $row->$appendedField = $this->$method($row);
            }
        }
    }

    /**
     * @property Collection|Paginator
     */
    private function makeCollectionFormats($results, array $formatFields): void
    {
        if (! $formatFields) {
            return;
        }

        $formatter = $this->formatter();

        foreach ($formatFields as $field => $method) {
            if (! $formatter->has($method)) {
                $class = get_called_class();
                throw new RuntimeException("В $class для поля $field указан несуществующий format метод $method."); 
            }
        }

        foreach ($results as $row) {
            foreach ($formatFields as $field => $method) {
                $row->$field = $formatter->format($method, $row->$field);
            }
        }
    }

    /**
     * @property Collection|Paginator
     */
    private function makeCollectionRelations($results, array $relations): void
    {
        if (! $relations) {
            return;
        }

        foreach ($relations as $relation) {

            $method = $this->camelCase($relation) . 'Relation';

            if (! method_exists($this, $method)) {
                $class  = get_called_class();
                throw new RuntimeException("В $class отсутствует relation метод $method");
            }

            $this->$method($results);
        }
    }

    /**
     * @property Collection|Paginator
     */
    private function makeCollectionHiddens($results, array $fields): void
    {
        if (! $fields) {
            return;
        }

        // @todo optimize

        foreach ($results as $row) {
            foreach ($fields as $field) {
                unset($row->$field);
            }
        }
    }

    private function camelCase(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return lcfirst(str_replace(' ', '', $value));
    }

    private function paramException(string $param, string $message): void
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
