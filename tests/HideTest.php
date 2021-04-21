<?php

namespace Tests;

use Tests\Mock\ReadQueries\ReadFieldsQuery;

class HideTest extends TestCase
{
    public function test_hide_scalar()
    {
        $query = (new ReadFieldsQuery(self::PERSON_ID, ["fields" => "id,first_name,last_name"]));
        $query->fields()->hide("last_name");

        $results = $query->results();

        $this->assertNotEmpty($results);
        $this->assertTrue(property_exists($results, "first_name"));
        $this->assertFalse(property_exists($results, "last_name"));
    }

    public function test_hide_array()
    {
        $query = (new ReadFieldsQuery(self::PERSON_ID, ["fields" => "id,first_name,last_name"]));
        $query->fields()->hide(["first_name", "last_name"]);

        $results = $query->results();

        $this->assertNotEmpty($results);
        $this->assertFalse(property_exists($results, "first_name"));
        $this->assertFalse(property_exists($results, "last_name"));
    }
}
