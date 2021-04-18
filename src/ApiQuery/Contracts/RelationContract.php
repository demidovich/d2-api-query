<?php

namespace D2\ApiQuery\Contracts;

interface RelationContract
{
    public function to($results, string $field): void;
}
