<?php

namespace Tests\Mock;

use D2\ApiQuery\FindApiQuery;
use Illuminate\Validation\Validator;

class BaseQuery extends FindApiQuery
{
    protected function validator(array $input, array $rules): Validator
    {
        return (new ValidatorFactory())->make($input, $rules);
    }
}
