<?php

namespace D2\ApiQuery\Components;

use Illuminate\Database\Query\Expression;
use RuntimeException;

class Fields
{
    private array  $sql          = []; // SQL table fields contains in the request param "fields="
    private array  $formats      = [];
    private array  $additions    = [];
    private array  $relations    = [];
    private array  $dependencies = [];

    public function toSql(?string $table = null): array
    {
        $fields  = $this->sql + $this->dependencies;
        $prefix  = $table ? "{$table}." : "";
        $results = [];

        foreach ($fields as $field => $rawSql) {
            $results[] = $rawSql !== true ? new Expression("$rawSql as $field") : $prefix.$field;
        }

        return $results;
    }

    /**
     * fullname   => addition|depends:first_name,last_name
     * fullname   => sql:first_name || ' ' || last_name
     * address    => sql:meta->>'address'|format:json_decode
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

        // Split string by pipe char
        // Skip SQL expression || and @|

        $prepared = preg_replace("/([^|@]{1})\s*\|\s*([^|@]{1})/", "\\1~~~\\2", $config);
        $segments = explode("~~~", $prepared);

        foreach ($segments as $segment) {

            $reg = "/^(?:
                (sql|format|depends)(?:\:(.+))|(addition|relation)
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
                case "addition":
                    $this->addAddition($field);
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
        // Если его нет в sql, additions или relations

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
        $this->formats[$field] = $formatter;
    }

    private function addAddition(string $field): void
    {
        $this->additions[$field] = true;
    }

    private function addRelation(string $field): void
    {
        $this->relations[$field] = true;
    }

    private function addDependencies(string $field, string $options): void
    {
        if (! preg_match("/^[a-z_]{1}([a-z\d_]*)(,[a-z_]{1}([a-z\d_]*))*$/i", $options)) {
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
     * Determine the fields to be hidden.
     *
     * @param object $rawItem Raw object from storage.
     * @return array
     */
    public function hidden($rawItem): array
    {
        $allFields = array_keys((array) $rawItem);

        return array_diff(
            $allFields,
            $this->visibled()
        );
    }

    public function additions(): array
    {
        return array_keys($this->additions);
    }

    public function relations(): array
    {
        return array_keys($this->relations);
    }

    public function formats(): array
    {
        return $this->formats;
    }

    /**
     * Determine if a given field name was addedd.
     */
    public function has(string $field): bool
    {
        return isset($this->additions[$field]) 
            || isset($this->relations[$field]) 
            || isset($this->sql[$field]);
    }

    /**
     * Get the visibled fields.
     * Does not include dependent fields.
     */
    public function visibled(): array
    {
        return array_keys(
            array_merge(
                $this->sql,
                $this->additions,
                $this->relations
            )
        );
    }
}
