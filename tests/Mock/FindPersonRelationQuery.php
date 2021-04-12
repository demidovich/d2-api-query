<?php

namespace Tests\Mock;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

class FindPersonRelationQuery extends BaseQuery
{
    protected string $sqlConnection = "default";

    protected string $table = "person";

    protected array $allowedFields = [
        "id",
    ];

    protected array $allowedRelations = [
        "city" => "depends:city_id",
    ];

    /**
     * @property Collection|Paginator
     */
    protected function cityRelation($results): void
    {
        $ids = $results->pluck("city_id")->toArray();

        $cities = FindCityQuery::fromArray([
            'ids'    => $ids,
            'count'  => 0,
            'fields' => 'id,name'
        ])->resultsBy('id');

        foreach ($results as $row) {
            $row->city = (isset($row->city_id) && isset($cities[$row->city_id])) ? $cities[$row->city_id] : null;
        }

        // foreach ($results as $row) {
        //     $row->city = (isset($row->city_id) && isset($cities[$row->city_id])) ? $cities[$row->city_id] : null;
        // }

        // foreach ($cities as $city) {
        //     $results->where("city_id", $city->id)->put("city", $city);
        // }
    }
}
