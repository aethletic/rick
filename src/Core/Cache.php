<?php

namespace Botify\Core;

class Cache
{
    public static function getMemcachedInstance($host, $port)
    {
        $mem = new \Memcached();
        $mem->addServer($host, $port);
        return $mem;
    }
}
