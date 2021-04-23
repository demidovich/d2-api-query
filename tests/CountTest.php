<?php

namespace Tests;

use Illuminate\Validation\ValidationException;
use Tests\Mock\FindQueries\FindFieldsQuery;

class CountTest extends TestCase
{
    public function test_default()
    {
        $count   = $this->db()->table("person")->count();
        $results = $this->findQueryItems(FindFieldsQuery::class);

        $this->assertEquals($count, count($results));
    }

    public function test_select_max()
    {
        $count   = $this->db()->table("person")->count();
        $results = $this->findQueryItems(FindFieldsQuery::class, ["count" => 0]);

        $this->assertEquals($count, count($results));
    }

    public function test_select_value()
    {
        $results = $this->findQueryItems(FindFieldsQuery::class, ["count" => 3]);

        $this->assertEquals(3, count($results));
    }

    public function test_max_value_exception()
    {
        $this->expectException(ValidationException::class);

        $query = (new FindFieldsQuery(["count" => 4]));
        $query->setMaxCount(3);

        $query->results();
    }

    public function test_bad_param_exception()
    {
        $this->expectException(ValidationException::class);

        $this->findQueryItems(FindFieldsQuery::class, ["count" => "not_integer"]);
    }

    protected function query(array $input = []): array
    {
        $results = (new FindFieldsQuery($input));

        return isset($results['data']) ? $results['data'] : $results;
    }
}
