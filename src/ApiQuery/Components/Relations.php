<?php

namespace D2\ApiQuery\Components;

use RuntimeException;

class Relations
{
    private Fields $fields;
    private array  $relations = [];

    public function __construct(Fields $fields)
    {
        $this->fields = $fields;
    }

    public function add(string $relation, string $config): void
    {
        $this->relations[$relation] = $relation;

        $segments = explode("|", $config);

        foreach ($segments as $segment) {

            if (! preg_match("/^(fields|depends)(?:\:(.+))$/", $segment, $match)) {
                throw new RuntimeException(
                    sprintf('Некорректная конфигурация "%s" поля "%s".', $segment, $relation)
                );
            }

            $name    = $match[1];
            $options = $match[2];

            if ($name == "depends") {
                $this->addDependencies($relation, $options);
            }

            // elseif ($name == "fields") {
            //     $this->addFields($relation, $options, $requestedFields);
            // }
        }
    }

    private function addDependencies(string $field, string $options): void
    {
        $dependencies = explode(",", $options);

        foreach ($dependencies as $field) {
            $this->fields->addDependency($field);
        }
    }

    // private function addFields(string $relation, string $options, array $requestedFields): void
    // {
    //     $allowedFields = explode(",", $options);

    //     if (($deniedFields = array_diff($requestedFields, $allowedFields))) {
    //         $this->paramException(
    //             "with",
    //             sprintf("Поля %s отношения {$name} отсутствуют в списке разрешенных.", implode(", ", $deniedFields))
    //         );
    //     }
    // }

    public function all(): array
    {
        return array_values($this->relations);
    }
}
