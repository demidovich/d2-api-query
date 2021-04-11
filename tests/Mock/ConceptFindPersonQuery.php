<?php

namespace Tests\Mock;

use Illuminate\Support\Collection;

class ConceptFindPersonQuery extends BaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "first_name",
        "last_name",
        "fullname"   => "append:fullname|depends:first_name,last_name",
        "created_at" => "sql:to_json(created_at)",
        "updated_at" => "format:json_date",
    ];

    protected array $allowedRelations = [
        "city" => "fields:id,name|depends:city_id"
    ];

    protected array $rules = [
        "city_id" => "nullable|integer",
    ];

    public function fullnameAppend(object $row): void
    {
        $row->fullname = "{$row->first_name} {$row->last_name}";
    }

    public function cityRelation(Collection $results, array $fields): void
    {

    }
}
