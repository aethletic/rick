<?php

namespace Aethletic\Telegram\Core;

class Session
{
    public static function Memcached($host, $port)
    {
        $mem = new \Memcached();
        $mem->addServer($host, $port);
        return $mem;
    }
}
