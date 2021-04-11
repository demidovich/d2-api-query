<?php

namespace D2\ApiQuery;

use D2\ApiQuery\Components\Fields;
use D2\ApiQuery\Components\Relations;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * ?created_at[leq]=2010-01-01&fields=id,name,role.id&sort[created_at]=asc
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
        $this->relations = $this->relationsInstance($this->input);
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
        $sql    = $this->sql();
        $fields = $this->fields;

        $this->selectFields($sql, $fields);
        $this->before($sql);

        $results = $this->limitedResults($sql);

        // $this->setAppends($results);
        // $this->setHidden($results);
        $this->after($results);
        $this->hideFields($results, $fields);

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
        return $this->fields->enabled($name);
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
        if (! isset($input['fields'])) {
            return new Fields($this->allowedFields);
        }

        $inputFields = explode(',', $input['fields']);
        $inputFields = array_unique($inputFields);
        $requested   = [];
        $denied      = [];

        foreach ($inputFields as $field) {
            if (isset($this->allowedFields[$field])) {
                $requested[$field] = $this->allowedFields[$field];
            } else {
                $denied[] = $field;
            }
        }

        if ($denied) {
            $this->paramException(
                "fields",
                sprintf("Поля {$denied} отсутствуют в списке разрешенных.", implode('", "', $denied))
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

    private function selectFields(Builder $sql, Fields $fields): void
    {
        $sql->select(
            $fields->toSql()
        );
    }

    private function hideFields(Collection $results, Fields $fields): void
    {
        $hidden = $fields->hidden();

        if (! $hidden) {
            return;
        }

        // @todo optimize

        foreach ($results as $row) {
            foreach ($hidden as $field) {
                unset($row->$field);
            }
        }
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
