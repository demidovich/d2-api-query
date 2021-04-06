<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule;

        $capsule->addConnection(
            [
                "driver"   => "pgsql",
                "host"     => "postgres",
                "database" => "d2",
                "username" => "d2",
                "password" => "d2"
            ], 
            "default"
        );

         $capsule->setAsGlobal();
    }

    protected function db(): ConnectionInterface
    {
        return Capsule::connection("default");
    }
}
