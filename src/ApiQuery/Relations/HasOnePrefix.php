<?php

namespace D2\ApiQuery\Relations;

use D2\ApiQuery\Contracts\RelationContract;

/**
 * results
 *     id = 1
 *     address_city = Krasnodar
 *     address_street = Severnaya
 * 
 * new HasOnePrefix($results, "address")
 * 
 * results
 *     id = 1
 *     address
 *         city = Krasnodar
 *         street = Severnaya
 */
class HasOnePrefix implements RelationContract
{
    public function to($results, string $field): void
    {
        $relation = new \stdClass();

        foreach ($results as $resultsField => $value) {
            if (preg_match("/^{$field}_(.+)$/", $resultsField, $match)) {
                $relation->$match[1] = $value;
                unset($results->$resultsField);
            }
        }

        $results->$field = $relation;
    }
}
