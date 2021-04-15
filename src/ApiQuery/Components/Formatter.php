<?php

namespace D2\ApiQuery\Components;

use D2\ApiQuery\Contracts\FormatterContract;

class Formatter implements FormatterContract
{
    public function has(string $method): bool
    {
        return method_exists($this, $method);
    }

    public function format(string $method, $value, ...$params)
    {
        return $this->$method($value, $params);
    }
}
