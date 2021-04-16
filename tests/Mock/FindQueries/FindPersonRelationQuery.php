<?php

namespace Tests\Mock\FindQueries;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Tests\Mock\FindBaseQuery;

class FindPersonRelationQuery extends FindBaseQuery
{
    protected string $table = "person";

    protected array $allowedFields = [
        "id",
        "city" => "relation|depends:city_id"
    ];

    /**
     * @property Collection|Paginator
     */
    protected function cityRelation($results): void
    {
        // $this
        //     ->hasOne(FindCityQuery::class, 'city_id', 'id')
        //     ->setFields('id,name')
        //     ->to($results);

        // $this
        //     ->hasOne(FindCityQuery::class)
        //     ->setLocalKey('city_id')
        //     ->setRelationKey('id')
        //     ->setFields('id,name')
        //     ->to($results);

        $ids = $this->collectionField($results, "city_id");

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

    /**
     * @property Collection|Paginator $results
     */
    private function collectionField($results, $field): array
    {
        $values = $results->pluck($field)->toArray();

        return array_unique($values);
    }
}
