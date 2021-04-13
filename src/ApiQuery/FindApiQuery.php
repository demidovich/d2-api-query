<?php

namespace D2\ApiQuery;

use D2\ApiQuery\Components\Fields;
use D2\ApiQuery\Components\Relations;
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
abstract class FindApiQuery
{
    protected  string  $sqlConnection;
    protected  string  $table;
    protected  array   $rules = [];
    protected  array   $allowedFields = [];    // ['field'    => configuration ]
    protected  array   $allowedRelations = []; // ['relation' => 'field1,field2']
    protected  int     $maxCount = 1000;
    protected  int     $perPage  = 25;

    private    Builder    $sql;
    private    Fields     $fields;
    private    Relations  $relations;
    private    array      $input = [];

    public function __construct(array $input, ...$params)
    {
        $rules = [
            'fields' => 'nullable|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/',
            'with'   => 'nullable|array',
            'with.*' => 'required|regex:/^[a-z\d_]+$/',
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
        $this->sql   = Capsule::connection($this->sqlConnection)->table($this->table)->select();

        $this->fields = $this->fieldsInstance(
            $this->allowedFields, 
            $this->input
        );

        $this->relations = $this->relationsInstance(
            $this->fields, 
            $this->allowedRelations, 
            $this->input
        );
    }

    public static function fromArray(array $input, ...$params): self
    {
        $class = get_called_class();

        return new $class($input, $params);
    }

    protected abstract function validator(array $input, array $rules): Validator;

    protected function rules(): array
    {
        return $this->rules;
    }

    /**
     * @return Collection|Paginator
     */
    public function results()
    {
        $sql       = $this->sql();
        $fields    = $this->fields;
        $relations = $this->relations;

        $this->before($sql);

        $sql->select($fields->sql());
        $results = $this->limitedResults($sql);

        if ($results->count() > 0) {
            $this->makeResultsAppends($results, $fields->appends());
            $this->makeResultsFormats($results, $fields->formats());
            $this->makeResultsRelations($results, $relations->all());
            $this->after($results);
            $this->makeResultsHiddens($results, $fields->hidden());
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

        return $fields;
    }

    private function relationsInstance(Fields $fields, array $allowed, array $input): Relations
    {
        $relations = new Relations($fields);

        if (! isset($input['with']) && ! isset($input['fields'])) {
            foreach ($allowed as $relation => $config) {
                $relations->add($relation, $config);
            }
            return $relations;
        }

        if (! isset($input['with'])) {
            return $relations;
        }

        $prepared = [];
        $denied   = [];

        foreach ($input['with'] as $relation) {
            if (isset($this->allowedRelations[$relation])) {
                $prepared[$relation] = $this->allowedRelations[$relation];
            } else {
                $denied[] = $relation;
            }
        }

        // foreach ($input['with'] as $name => $fieldsSerialized) {

        //     // Во входных параметрах было
        //     // with[]=название_отношения

        //     if (is_int($name)) {
        //         $name = $fieldsSerialized;
        //         $fieldsSerialized = null;
        //     }

        //     if (isset($this->allowedRelations[$name])) {
        //         $prepared[$name] = $this->allowedRelations[$name];
        //     } else {
        //         $denied[] = $name;
        //     }
        // }

        if ($denied) {
            $this->paramException(
                "with",
                sprintf('Отношения "%s" отсутствуют в списке разрешенных.', implode('", "', $denied))
            );
        }

        foreach ($prepared as $name => $config) {
            $relations->add($name, $config);
        }

        return $relations;
    }

    /**
     * @property Collection|Paginator
     */
    private function makeResultsAppends($results, array $appends): void
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
    private function makeResultsFormats($results, array $formatFields): void
    {
        if (! $formatFields) {
            return;
        }

        $formatters = [];
        foreach ($formatFields as $field => $formatter) {

            $method = $this->camelCase($formatter) . 'Formatter';

            if (! method_exists($this, $method)) {
                $class  = get_called_class();
                throw new RuntimeException("В $class отсутствует format метод $method.");
            }

            $formatters[$field] = $method;
        }

        foreach ($results as $row) {
            foreach ($formatters as $field => $method) {
                $row->$field = $this->$method($row->$field);
            }
        }
    }

    /**
     * @property Collection|Paginator
     */
    private function makeResultsRelations($results, array $relations): void
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
    private function makeResultsHiddens($results, array $fields): void
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
