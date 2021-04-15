<?php

namespace Tests\Mock;

use D2\ApiQuery\Contracts\FormatterContract;
use D2\ApiQuery\CollectionQuery;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Validator;

class FindBaseQuery extends CollectionQuery
{
    protected function validator(array $input, array $rules): Validator
    {
        return (new ValidatorFactory())->make($input, $rules);
    }

    protected function formatter(): FormatterContract
    {
        return new Formatter();        
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
