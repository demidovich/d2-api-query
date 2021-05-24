<?php

namespace D2\ApiQuery;

use RuntimeException;

trait MultiplePrimaryKey
{
    protected function sqlFind($key): void
    {
        $class = get_called_class();

        if (! isset($this->multiplePrimaryKey)) {
            throw new RuntimeException("Not configured property \"multiplePrimaryKey\". Query class $class.");
        }

        if (! is_array($key)) {
            throw new RuntimeException("The multiple primary key value must be an array. Query class $class.");
        }

        $prefix = isset($this->table) ? "{$this->table}." : "";

        foreach ($this->multiplePrimaryKey as $field) {
            if (! isset($key[$field])) {
                throw new RuntimeException("Not passed part \"$field\" of multiple primary key value. Query class $class.");
            }
            $this->sql()->where($prefix.$field, $key[$field]);
        }
    }
}
