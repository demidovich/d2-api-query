<?php

namespace D2\ApiQuery\Components;

use RuntimeException;

/**
 * 
 */
class Fields
{
    private array $sql          = [];
    private array $formats      = [];
    private array $appends      = [];
    private array $dependencies = [];

    public function __construct(array $fields)
    {
        foreach ($fields as $field => $config) {
            $this->add($field, $config);
        }
    }

    /**
     * fullname   => append:first_name,last_name
     * fullname   => sql:first_name || ' ' || last_name
     * created_at => sql:to_json(created_at)
     * updated_at => format:json_date
     */
    private function add(string $field, ?string $config = null): void
    {
        if (! $config) {            
            $this->sql[$field] = $field;
            return;
        }

        $config   = preg_replace("/([a-z\d]{1})\s*\|\s*([a-z\d]{1})/i", "\\1~~~\\2", $config);
        $segments = explode("~~~", $config);

        foreach ($segments as $segment) {

            if (! preg_match("/^(sql|format|append)(?:\:(.+))$/", $segment, $match)) {
                throw new RuntimeException(
                    sprintf('Некорректная конфигурация поля "%s".', $field)
                );
            }

            $name    = $match[1];
            $options = $match[2];

            if ($name == "sql") {
                $this->addSql($field, $options);
            }

            elseif ($name == "format") {
                $this->addFormat($field, $options);
            }

            elseif ($name == "append") {
                $this->addAppend($field, $options);
            }
        }

        if (! isset($this->appends[$field]) && ! isset($this->sql[$field])) {
            $this->sql[$field] = $field;
        }
    }

    private function addSql(string $field, ?string $options): void
    {
        if (! $options) {
            throw new RuntimeException(
                sprintf('Некорректное значение параметра "sql" поля "%s".', $field)
            );
        }

        $this->sql[$field] = $options;
    }

    private function addFormat(string $field, ?string $options): void
    {
        if (! preg_match("/^[a-z\d_]+$/i", $options)) {
            throw new RuntimeException(
                sprintf('Некорректное значение параметра "format" поля "%s".', $field)
            );
        }

        $this->formats[$field] = $options;
    }

    private function addAppend(string $field, ?string $options): void
    {
        $this->appends[$field] = $field;

        if (! $options) {
            return;
        }

        if (! preg_match("/^[a-z0-9_]+(,[a-z0-9_]+)*$/i", $options)) {
            throw new RuntimeException(
                sprintf('Некорректное значение параметра "append" поля "%s".', $field)
            );
        }

        foreach (explode(",", $options) as $dependency) {
            $this->addDependency($dependency);
        }
    }

    public function addDependency(string $field): void
    {
        $this->dependencies[$field] = $field;
    }

    public function hidden(): array
    {
        $requested = $this->sql + $this->dependencies;

        return array_keys(
            array_diff(
                $this->dependencies,
                $requested
            )
        );
    }

    public function sql(): array
    {
        $fields  = $this->sql + $this->dependencies;
        $results = [];

        foreach ($fields as $field => $sql) {
            $results[] = $field === $sql ? $field : "$sql as $field";
        }

        return $results;
    }

    public function formats(): array
    {
        return $this->formats;
    }

    public function appends(): array
    {
        return $this->appends;
    }

    public function has(string $field): bool
    {
        return (isset($this->sql[$field]) || isset($this->appends[$field]));
    }
}
