<?php

namespace D2\ApiQuery;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
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
        $this->fields    = new Fields($this->allowedFields);
        $this->relations = new Relations($this->allowedRelations);

        $this->enableFields($this->input, $this->fields);
        $this->enableRelations($this->input, $this->relations);
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
                "Превышено максимальное значение count $this->maxCount."
            );
        }

        return $sql->limit($count)->get();
    }

    // /**
    //  * Запрашиваемые поля
    //  * Все fields без "." будут преобразованы в "table.field"
    //  * Это нужно для безопасного выполнения join
    //  */
    // private function setFields(Builder $sql): void
    // {
    //     $fields = $this->requestedFields;
    //     $fields = $this->prefixableFields($this->table, array_unique($fields));

    //     $sql->select($fields);
    // }

    // private function prefixableFields(string $table, array $fields): array
    // {
    //     $results = [];

    //     foreach ($fields as &$row) {
    //         if (false === stripos($row, ".")) {
    //             $results[] = "$table.$row";
    //         }
    //     }

    //     return $results;
    // }

    private function enableFields(array $input, Fields $fields): void
    {
        if (! isset($input['fields'])) {
            $fields->enableAll();
            return;
        }

        $inputFields = explode(',', $input['fields']);
        $inputFields = array_unique($inputFields);

        foreach ($inputFields as $field) {
            if ($fields->allowed($field)) {
                $fields->enable($field);
            } else {
                $this->paramException(
                    "fields",
                    "Некорректное значение параметра. Поле \"{$field}\" отсутствует в списке разрешенных."
                );
            }
        }
    }

    private function enableRelations(array $input, Relations $relations): void
    {
        if (! isset($input['with']) && ! isset($input['fields'])) {
            $relations->enableAll();
            return;
        }

        if (! isset($input['with'])) {
            return;
        }

        foreach ($input['with'] as $name => $fieldsSerialized) {

            // Во входных параметрах было
            // with[]=название_отношения

            if (is_int($name)) {
                $name = $fieldsSerialized;
                $fieldsSerialized = null;
            }

            if (! $relations->allowed($name)) {
                $this->paramException(
                    "with",
                    "Отношение \"{$name}\" отсутствует в списке разрешенных."
                );
            }

            $allowedFields = explode(",", $this->allowedRelations[$name]);

            if ($fieldsSerialized) {
                $fields = explode(",", $fieldsSerialized);
                if (($denied = array_diff($fields, $allowedFields))) {
                    $this->paramException(
                        "with[$name]",
                        sprintf("Поля %s запрещены", implode(", ", $denied))
                    );
                }
            } else {
                $fields = $allowedFields;
            }

            $relations->enable($name, $fields);
        }
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
