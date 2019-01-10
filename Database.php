<?php

namespace App;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    private $config;

    public function __construct()
    {
        $this->config = [
            'driver'    => 'mysql',
            'host'      => env('DATABASE_HOST'),
            'port'      => env('DATABASE_PORT'),
            'database'  => env('DATABASE_DB'),
            'username'  => env('DATABASE_USER'),
            'password'  => env('DATABASE_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ];
    }

    public function connect()
    {
        $capsule = new Capsule;

        $capsule->addConnection($this->config);

        $capsule->setAsGlobal();

        $capsule->bootEloquent();
    }
}