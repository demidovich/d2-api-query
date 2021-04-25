<?php

namespace Tests\Mock;

use D2\ApiQuery\Contracts\FormatterContract;
use D2\ApiQuery\CollectionQuery;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Validation\Validator;

class FindBaseQuery extends CollectionQuery
{
    protected string $sqlConnection = "default";

    protected function validator(array $input, array $rules): Validator
    {
        return (new ValidatorFactory())->make($input, $rules);
    }

    protected function formatter(): FormatterContract
    {
        return new Formatter();        
    }

    protected function sqlConnection(string $connection): Connection
    {
        return Capsule::connection($connection);
    }
}
