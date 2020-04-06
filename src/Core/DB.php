<?php

namespace Aethletic\Telegram\Core;

class DB
{
    public function connect($config)
    {
        $factory = new \Database\Connectors\ConnectionFactory();
        $driver = mb_strtolower($config['db.driver']);

        if ($driver == 'sqlite') {
            return $factory->make([
                'driver'    => 'sqlite',
                'database' => $config['db.path'],
            ]);
        }

        if ($driver == 'mysql') {
            return $factory->make([
                'driver'    => 'mysql',
                'host'      => $config['db.host'],
                'username'  => $config['db.username'],
                'password'  => $config['db.password'],
                'charset'   => $config['db.charset'] ?? 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]);
        }
    }
}
