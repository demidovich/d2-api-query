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
    protected  array   $allowedFields = [];                 // ['field']
    protected  array   $allowedAppends = [];                // ['field']
    protected  array   $allowedAppendsDependencies = [];    // ['field']
    protected  array   $allowedRelations = [];              // ['relation' => 'field1,field2']
    protected  array   $hiddenFields = [];                  // ['field']
    protected  int     $maxCount = 1000;
    protected  int     $perPage  = 25;

    private    Builder $sql;
    private    array   $input = [];
    private    array   $requestedFields = [];
    private    array   $requestedAppends = [];
    private    array   $requestedRelations = [];

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
        ] + $this->queryRealisationRules();

        $validator = $this->validator($input, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->input = $validator->validated();
        $this->sql   = Capsule::connection($this->sqlConnection)->table($this->table)->select();

        $this->parseInput();
    }

    protected abstract function validator(array $input, array $rules): Validator;

    public static function fromArray(array $input, ...$params): self
    {
        $class = get_called_class();

        return new $class($input, $params);
    }

    // public static function fromRequest(Request $request): self
    // {
    //     $class = get_called_class();

    //     return new $class($request);
    // }

    /**
     * Если в реализации поиска существует метод rules()
     * данные будут получены из него. Иначе будет использоваться
     * атрибут rules
     */
    private function queryRealisationRules(): array
    {
        if (method_exists($this, 'rules')) {
            $rules = $this->{'rules'}();
            if (! is_array($rules)) {
                return [];
            }
        } else {
            $rules = $this->rules;
        }

        return $rules;
    }

    /**
     * @return Collection|Paginator
     */
    public function results()
    {
        $sql = $this->sql();

        $this->setFields($sql);
        $this->setRelations($sql);
        $this->before($sql);

        $results = $this->limitedResults($sql);

        $this->setAppends($results);
        $this->setHidden($results);
        $this->after($results);

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
        return array_key_exists($name, $this->requestedFields) || array_key_exists($name, $this->requestedAppends);
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

    /**
     * Запрашиваемые поля
     * Все fields без "." будут преобразованы в "table.field"
     * Это нужно для безопасного выполнения join
     */
    private function setFields(Builder $sql): void
    {
        $fields = $this->requestedFields();
        $fields = $this->prefixableFields($this->table, array_unique($fields));

        $sql->select($fields);
    }

    private function prefixableFields(string $table, array $fields): array
    {
        $results = [];

        foreach ($fields as &$row) {
            if (false === stripos($row, ".")) {
                $results[] = "$table.$row";
            }
        }

        return $results;
    }

    /**
     * Запрашиваемые отношения
     */
    private function setRelations(Builder $sql): void
    {
        if (! $this->allowedRelations) {
            return;
        }

        if (! $this->requestedRelations()) {
            return;
        }

        $sql->with(
            $this->requestedRelations()
        );
    }

    /**
     * Скрыть поле
     */
    protected function hideField(string $field): void
    {
        $this->hiddenFields[] = $field;
    }

    /**
     * Добавление кастомных мутаторов модели к результатам запроса.
     * Для этого в модели должны быть соответствующие getAttribute().
     * В модели $appends указывать не нужно, иначе они будут присутствовать
     * во всех результатах поиска модели.
     * Appends будут динамически проинициализированы здесь на основе
     * запрошенных полей.
     *
     * @param Collection|Paginator $results
     */
    private function setAppends($results): void
    {
        if (! $this->allowedAppends) {
            return;
        }

        $appends = $this->requestedAppends();

        if (! $appends) {
            return;
        }

        foreach ($results as $item) {
            $item->setAppends($appends);
        }

        $hiddenDependencies = array_diff(
            $this->allowedAppendsDependencies,
            $this->allowedFields
        );

        $results->makeHidden($hiddenDependencies);
    }

    /**
     * Скрытие полей из полученных данных.
     *
     * @param Collection|Paginator $results
     */
    private function setHidden($results): void
    {
        if (! $this->hiddenFields) {
            return;
        }

        $results->makeHidden($this->hiddenFields);
    }

    /**
     * Парсинг запроса
     * Выяснение необходимых полей, дополнительных атрибутов и связей
     * Заполнение
     *     requestedFields
     *     requestedAppends
     *     requestedRelations
     */
    private function parseInput()
    {
        // fields, appends

        if (isset($this->input['fields'])) {
            $inputFields = explode(',', $this->input['fields']);
            $inputFields = array_unique($inputFields);
            foreach ($inputFields as $field) {
                if (in_array($field, $this->allowedFields)) {
                    $fields[] = $field;
                } elseif (in_array($field, $this->allowedAppends)) {
                    $appends[] = $field;
                } else {
                    $this->paramException(
                        "fields",
                        "Некорректное значение параметра. Поле \"{$field}\" отсутствует в списке разрешенных."
                    );
                }
            }
        }

        // Если на входе нет fields отдаем все доступные поля таблицы

        else {
            $fields  = $this->allowedFields;
            $appends = $this->allowedAppends;
        }

        // relations

        if (isset($this->input['with'])) {
            $relations = $this->parseInputRelations($this->input['with']);
        }

        // Если на входе нет вообще ничего, ко всем доступным полям таблицы
        // добавляем все доступные отношения

        elseif(! isset($this->input['fields'])) {
            if ($this->allowedRelations) {
                foreach ($this->allowedRelations as $relationName => $relationFields) {
                    $relations[] = "{$relationName}:{$relationFields}";
                }
            }
        }

        if ($this->allowedAppendsDependencies) {
            $fields = array_merge($fields, $this->allowedAppendsDependencies);
        }

        if (! empty($fields)) {
            $this->requestedFields = $fields;
        }

        if (! empty($appends)) {
            $this->requestedAppends = $appends;
        }

        if (! empty($relations)) {
            $this->requestedRelations = $relations;
        }
    }

    private function parseInputRelations(array $with): array
    {
        $relations = [];

        foreach ($with as $name => $fieldsString) {

            // Во входных параметрах было
            // with[]=название_отношения

            if (is_int($name)) {
                $this->paramException(
                    "with",
                    "Некорректное значение параметра. Пример корректного запроса with[relation]=field1,field2."
                );
            }

            if (! isset($this->allowedRelations[$name])) {
                $this->paramException(
                    "with",
                    "Некорректное значение параметра. Отношение \"{$name}\" отсутствует в списке разрешенных."
                );
            }

            $fields  = explode(",", $fieldsString);
            $allowed = explode(",", $this->allowedRelations[$name]);

            if (($denied = array_diff($fields, $allowed))) {
                $this->paramException(
                    "with[$name]",
                    sprintf("Поля %s запрещены", implode(", ", $denied))
                );
            }

            $relations[] = $name.":".implode(",", $fields);
        }

        return $relations;
    }

    private function requestedFields(): array
    {
        return $this->requestedFields;
    }

    private function requestedAppends(): array
    {
        return $this->requestedAppends;
    }

    private function requestedRelations(): array
    {
        return $this->requestedRelations;
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
