<?php

namespace Tests;

use Tests\Mock\FindPersonQuery;
use Tests\Mock\FindPersonQueryConcept;

class DevTest extends TestCase
{
    public function test_dev()
    {
        $this->assertTrue(true);
        return;

        $sql = [
            "id" => "id",
            "first_name" => "first_name",
            "last_name" => "last_name",
          
        ];

        $dependencies = [
            "id" => "id",
        ];

        $hidden = array_diff($dependencies, $sql);

        dd($hidden);

        // v3

        $s = "append|sql:first_name || ' ' || last_name|format:human_name|append|depends:first_name,last_name|append";

        $s = preg_replace("/([a-z\d]{1})\s*\|\s*([a-z\d]{1})/i", "\\1~~~\\2", $s);
        $m = explode("~~~", $s);

        dd($m);

        // v2

        $s = "sql:first_name || ' ' || last_name|format:human_name|append|depends:first_name,last_name";
        $s = "append||sql:first_name || ' ' || last_name|format:human_name|append|depends:first_name,last_name|append";

        preg_match_all('/
            (sql|format|append|depends)
            (?:
                \:(.+?)?(?=
                    (?:\|(?:sql|format|append|depends)|$)
                )
            )?
        /x', $s, $matches);

        dd(
            $s,
            $matches
        );

        // v1

        // $s = "append|depends:first_name,last_name";
        // $s = "sql:first_name || ' ' || last_name|format:human_name";
        $s = "sql:first_name || ' ' || last_name|format:human_name|append|depends:first_name,last_name";

        // array: [
        //     0 => ""
        //     1 => "sql"
        //     2 => "first_name || ' ' || last_name"
        //     3 => "format"
        //     4 => "human_name"
        //     5 => "append"
        //     6 => ""
        //     7 => "depends"
        //     8 => "first_name,last_name"
        // ]
          

        $m = preg_split("/
        \|?
        (sql|append|depends|format)
        (?:\||:|$)
        /x", $s, -1, PREG_SPLIT_DELIM_CAPTURE);

        dd($m);
    }
}
