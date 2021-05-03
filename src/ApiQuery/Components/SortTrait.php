<?php

namespace D2\ApiQuery\Components;

trait SortTrait
{
    /**
     * Determine if the query has a sorted field.
     *
     * @return bool
     */
    public function hasSort(string $field): bool
    {
        return isset($this->input["sort"][$field]);
    }

    /**
     * Get the sort direction for a field.
     * 
     * @params string $field
     * @return string asc or desc
     */
    public function sortDirection(string $field): string
    {
        return $this->input["sort"][$field];
    }
}
