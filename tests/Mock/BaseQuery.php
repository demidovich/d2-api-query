<?php

namespace Tests\Mock;

use D2\ApiQuery\FindApiQuery;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Validator;

class BaseQuery extends FindApiQuery
{
    protected function validator(array $input, array $rules): Validator
    {
        return (new ValidatorFactory())->make($input, $rules);
    }

    // public static function fromRequest(Request $request): self
    // {
    //     $class = get_called_class();

    //     return new $class($request);
    // }

    public function sqlTable(string $table): Builder
    {
        return Capsule::connection($this->sqlConnection)->table($table);
    }
}
