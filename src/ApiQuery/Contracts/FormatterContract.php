<?php

namespace D2\ApiQuery\Contracts;

interface FormatterContract
{
    public function has(string $method): bool;

    public function format(string $method, $value, ...$params);
}
