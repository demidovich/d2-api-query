<?php

namespace Tests\Mock;

use D2\ApiQuery\Components\Formatter as BaseFormatter;

class Formatter extends BaseFormatter
{
    protected function json_date($value)
    {
        return $value ? date("c", strtotime($value)) : null;
    }
}
