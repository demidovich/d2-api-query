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
 * ?created_at[leq]=2010-01-01&fields=id,name,role.id&sort[created_at]=asc
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
            'with.*' => 'required|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/',
            'sort'   => 'nullable|array',
            'sort.*' => 'in:asc,desc',
            'count'  => 'nullable|int',
            'page'   => 'nullable|int',
        ] + $this->rules();

        $validator = $this->validator($input, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->input     = $validator->validated();
        $this->sql       = Capsule::connection($this->sqlConnection)->table($this->table)->select();
        $this->fields    = $this->fieldsInstance($this->input);
        $this->relations = $this->relationsInstance($this->fields, $this->input);
    }

    protected abstract function validator(array $input, array $rules): Validator;

    public static function fromArray(array $input, ...$params): self
    {
        $class = get_called_class();

        return new $class($input, $params);
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
        $sql       = $this->sql();
        $fields    = $this->fields;
        $relations = $this->relations;

        $this->before($sql);

        $sql->select($fields->sql());
        $results = $this->limitedResults($sql);

        if ($results->count() > 0) {
            $this->makeResultsAppends($results, $fields->appends());
            $this->makeResultsFormats($results, $fields->formats());
            //$this->makeResultsRelations($results, $relations->all());
            $this->after($results);
            $this->makeResultsHiddens($results, $fields->hidden());
        }

        return $results;
    }

    public function sql(): Builder
    {
        return $this->sql;
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

    private function fieldsInstance(array $input): Fields
    {
        $allowedFieldsKeyable = [];

        foreach ($this->allowedFields as $k => $v) {
            if (is_int($k)) {
                $k = $v;
                $v = null;
            }
            $allowedFieldsKeyable[$k] = $v;
        }

        if (! isset($input['fields'])) {
            return new Fields($allowedFieldsKeyable);
        }

        $inputFields = explode(',', $input['fields']);
        $inputFields = array_unique($inputFields);
        $requested   = [];
        $denied      = [];

        foreach ($inputFields as $field) {
            if (array_key_exists($field, $allowedFieldsKeyable)) {
                $requested[$field] = $allowedFieldsKeyable[$field];
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

        return new Fields($requested);
    }

    private function relationsInstance(Fields $fields, array $input): Relations
    {
        if (! isset($input['with']) && ! isset($input['fields'])) {
            return new Relations($fields, $this->allowedRelations);
        }

        if (! isset($input['with'])) {
            return Relations::empty();
        }

        $relations = [];

        foreach ($input['with'] as $name => $fieldsSerialized) {

            // Во входных параметрах было
            // with[]=название_отношения

            if (is_int($name)) {
                $name = $fieldsSerialized;
                $fieldsSerialized = null;
            }

            if (! isset($this->allowedRelations[$name])) {
                $this->paramException(
                    "with",
                    "Отношение \"{$name}\" отсутствует в списке разрешенных."
                );
            }

            $allowedFields = explode(",", $this->allowedRelations[$name]);

            if ($fieldsSerialized) {
                $requestedFields = explode(",", $fieldsSerialized);
                if (($deniedFields = array_diff($requestedFields, $allowedFields))) {
                    $this->paramException(
                        "with",
                        sprintf("Поля %s отношения {$name} отсутствуют в списке разрешенных.", implode(", ", $deniedFields))
                    );
                }
            } else {
                $requestedFields = $allowedFields;
            }

            $relations[$name] = $requestedFields;
        }

        return new Relations($fields, $relations);
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
            $methods[] = $method;
        }

        foreach ($results as $row) {
            foreach ($methods as $method) {
                $this->$method($row);
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
                throw new RuntimeException("В $class отсутствует format метод $method");
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
