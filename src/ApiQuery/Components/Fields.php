<?php

namespace D2\ApiQuery\Components;

use RuntimeException;

class Fields
{
    private string $table;
    private array  $sql          = [];
    private array  $formats      = [];
    private array  $appends      = [];
    private array  $relations    = [];
    private array  $dependencies = [];
    private array  $hidden       = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * fullname   => append|depends:first_name,last_name
     * fullname   => sql:first_name || ' ' || last_name
     * created_at => sql:to_json(created_at)
     * updated_at => format:json_date
     * city       => relation|depends:city_id
     */
    public function add(string $field, ?string $config = null): void
    {
        if (! $config) {            
            $this->addSql($field);
            return;
        }

        $config   = preg_replace("/([a-z\d]{1})\s*\|\s*([a-z\d]{1})/i", "\\1~~~\\2", $config);
        $segments = explode("~~~", $config);

        foreach ($segments as $segment) {

            $reg = "/^(?:
                (sql|format|depends)(?:\:(.+))|(append|relation)
            )$/x";

            if (! preg_match($reg, $segment, $match)) {
                throw new RuntimeException(
                    sprintf('Некорректная конфигурация поля "%s" "%s".', $field, $segment)
                );
            }

            $name    = empty($match[1]) ? $match[3] : $match[1];
            $options = $match[2];

            switch ($name) {
                case "sql":
                    $this->addSql($field, $options);
                    break;
                case "format":
                    $this->addFormat($field, $options);
                    break;
                case "append":
                    $this->addAppend($field);
                    break;
                case "relation":
                    $this->addRelation($field);
                    break;
                case "depends":
                    $this->addDependencies($field, $options);
                    break;
            }
        }

        // Если поле имело параметр format оно еще не зарегистрировано

        if (! $this->has($field)) {
            $this->sql[$field] = $field;
        }
    }

    private function addSql(string $field, ?string $rawSql = null): void
    {
        $this->sql[$field] = $rawSql ? $rawSql : true;
    }

    private function addFormat(string $field, string $formatter): void
    {
        // if (! preg_match("/^[a-z\d_]+$/i", $formatter)) {
        //     throw new RuntimeException(
        //         sprintf('Некорректное значение параметра "format" поля "%s".', $field)
        //     );
        // }

        $this->formats[$field] = $formatter;
    }

    private function addAppend(string $field): void
    {
        $this->appends[$field] = true;
    }

    private function addRelation(string $field): void
    {
        $this->relations[$field] = true;
    }

    private function addDependencies(string $field, string $options): void
    {
        if (! preg_match("/^[a-z\d_]+(,[a-z\d_]+)*$/i", $options)) {
            throw new RuntimeException(
                sprintf('Некорректное значение параметра "depends" поля "%s".', $field)
            );
        }

        foreach (explode(",", $options) as $dependency) {
            $this->addDependency($dependency);
        }
    }

    public function addDependency(string $field): void
    {
        $this->dependencies[$field] = true;
    }

    /**
     * @property mixed $field
     */
    public function hide($field): void
    {
        if (is_array($field)) {
            foreach ($field as $f) {
                $this->hidden[$f] = $f;
            }
        } else {
            $this->hidden[$field] = $field;
        }
    }

    public function hidden(): array
    {
        $hidden = $this->hidden;

        if ($this->dependencies) {
            $requested = $this->sql + $this->dependencies;
            foreach ($this->dependencies as $k => $v) {
                if (isset($requested[$k])) {
                    $hidden[$k] = true;
                }
            }
        }

        return $hidden ? array_keys($hidden) : [];
    }


    public function sql(): array
    {
        $fields  = $this->sql + $this->dependencies;
        $results = [];

        foreach ($fields as $field => $rawSql) {
            $results[] = $rawSql !== true ? "$rawSql as $field" : "$this->table.$field";
        }

        return $results;
    }

    public function appends(): array
    {
        return array_keys($this->appends);
    }

    public function relations(): array
    {
        return array_keys($this->relations);
    }

    public function formats(): array
    {
        return $this->formats;
    }

    public function has(string $field): bool
    {
        return isset($this->appends[$field]) 
            || isset($this->relations[$field]) 
            || isset($this->sql[$field]);
    }
}