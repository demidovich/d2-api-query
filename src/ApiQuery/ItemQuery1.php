<?php

namespace D2\ApiQuery;

use D2\ApiQuery\Components\Fields;
use D2\ApiQuery\Components\FieldsTrait;
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
abstract class ItemQuery1
{
    use FieldsTrait;

    protected  string  $sqlConnection;
    protected  string  $table;
    protected  string  $primaryKey;
    // protected  array   $rules = [];
    protected  array   $allowedFields = [];    // ['field'    => configuration ]
    // protected  int     $maxCount = 1000;
    // protected  int     $perPage  = 25;

    private Builder $sql;
    private Fields  $fields;
    private array   $input;
    private $key;

    public function __construct(array $input, $key, ...$params)
    {
        $validator = $this->validator($input, [
            'fields' => 'nullable|regex:/^[a-z\d_]+(?:,[a-z\d_]+)*$/'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->input = $validator->validated();
        $this->sql   = Capsule::connection($this->sqlConnection)->table($this->table);
        $this->key   = $key;

        $this->initFields(
            $this->allowedFields, 
            $this->input
        );
    }

    public static function fromArray(array $input, ...$params): self
    {
        $class = get_called_class();

        return new $class($input, $params);
    }

    protected function keyName(): string
    {
        return "{$this->table}.{$this->primaryKey}";
    }

    protected abstract function validator(array $input, array $rules): Validator;

    protected abstract function formatter(): FormatterContract;

    // protected function rules(): array
    // {
    //     return $this->rules;
    // }

    /**
     * @return Collection|Paginator
     */
    public function results()
    {
        $sql    = $this->sql();
        $fields = $this->fields();

        $sql->select($fields->sql());
        $this->before($sql);

        $item = $sql->where($this->keyName(), $this->key)->first();

        if ($item) {

            $appendMethods = $this->appendMethods($fields->appends());
            $this->makeItemAppends($item, $appendMethods);

            $this->ensureCorrectFormatters($fields->formats());
            $this->makeItemFormats($item, $fields->formats());

            //$this->makeItemRelations($results, $fields->relations());

            $this->after($item);
            $this->makeItemHiddens($item, $fields->hidden());
        }

        return $item;
    }

    // /**
    //  * @return Collection|Paginator
    //  */
    // public function resultsBy(string $key)
    // {
    //     return $this->results()->keyBy($key);
    // }

    // /**
    //  * @return Collection|Paginator
    //  */
    // private function limitedResults(Builder $sql)
    // {
    //     $input = $this->input;

    //     if (! array_key_exists('count', $input)) {
    //         return $sql->simplePaginate($this->perPage)->appends($input);
    //     }

    //     $count = (int) $input['count'];

    //     if ($count < 1) {
    //         $count = $this->maxCount;
    //     }

    //     if ($count > $this->maxCount) {
    //         $this->paramException(
    //             "count",
    //             "Превышено максимально допустимое значение count $this->maxCount."
    //         );
    //     }

    //     return $sql->limit($count)->get();
    // }

    public function sql(): Builder
    {
        return $this->sql;
    }

    // public function fields(): Fields
    // {
    //     return $this->fields;
    // }

    // public function setMaxCount(int $value): void
    // {
    //     $this->maxCount = $value;
    // }

    protected function before(Builder $sql): void
    {

    }

    /**
     * @property Collection|Paginator $results
     */
    protected function after($results): void
    {

    }

    // /**
    //  * Были ли запрошено поле в параметре запроса fields.
    //  */
    // protected function hasRequestedField(string $name): bool
    // {
    //     return $this->fields->has($name);
    // }

    // /**
    //  * Наличие во входных данных поля, описанного в rules.
    //  *
    //  * Метод полезен в одном случае: когда описано поле bool и нужно сделать
    //  * проверку, что этот фильтр в запросе существует и он имеет значение false.
    //  * В этом случает проверка if ($this->filter) в любом случае завершится
    //  * неудачей.
    //  */
    // protected function hasRequestedFilter(string $name): bool
    // {
    //     return array_key_exists($name, $this->input);
    // }

    // private function fieldsInstance(array $allowedRaw, array $input): Fields
    // {
    //     $fields = new Fields($this->table);

    //     $allowed = [];
    //     foreach ($allowedRaw as $k => $v) {
    //         if (is_int($k)) {
    //             $k = $v;
    //             $v = null;
    //         }
    //         $allowed[$k] = $v;
    //     }

    //     if (! isset($input['fields'])) {
    //         foreach ($allowed as $field => $config) {
    //             $fields->add($field, $config);
    //         }
    //         return $fields;
    //     }

    //     $requested = array_unique(explode(',', $input['fields']));
    //     $prepared  = [];
    //     $denied    = [];

    //     foreach ($requested as $field) {
    //         if (array_key_exists($field, $allowed)) {
    //             $prepared[$field] = $allowed[$field];
    //         } else {
    //             $denied[] = $field;
    //         }
    //     }

    //     if ($denied) {
    //         $this->paramException(
    //             "fields",
    //             sprintf('Поля "%s" отсутствуют в списке разрешенных.', implode('", "', $denied))
    //         );
    //     }

    //     foreach ($prepared as $field => $config) {
    //         $fields->add($field, $config);
    //     }

    //     return $fields;
    // }

    private function appendMethods(array $appends): array
    {
        $methods = [];

        foreach ($appends as $append) {
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
    private function makeItemAppends($item, array $appendMethods): void
    {
        foreach ($appendMethods as $appendedField => $method) {
            $item->$appendedField = $this->$method($item);
        }
    }

    private function ensureCorrectFormatters(array $formats): void
    {
        if (! $formats) {
            return;
        }

        foreach ($formats as $field => $method) {
            if (! $this->formatter()->has($method)) {
                $class = get_called_class();
                throw new RuntimeException("В $class для поля $field указан несуществующий format метод $method."); 
            }
        }
    }

    /**
     * @property object
     */
    private function makeItemFormats($item, array $fieldMethods): void
    {
        foreach ($fieldMethods as $field => $method) {
            $item->$field = $this->formatter()->format($method, $item->$field);
        }
    }

    // /**
    //  * @property Collection|Paginator
    //  */
    // private function makeItemRelations($results, array $relations): void
    // {
    //     if (! $relations) {
    //         return;
    //     }

    //     foreach ($relations as $relation) {

    //         $method = $this->camelCase($relation) . 'Relation';

    //         if (! method_exists($this, $method)) {
    //             $class  = get_called_class();
    //             throw new RuntimeException("В $class отсутствует relation метод $method");
    //         }

    //         $this->$method($results);
    //     }
    // }

    /**
     * @property Collection|Paginator
     */
    private function makeItemHiddens($item, array $fields): void
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
