<?php

namespace Botify\Extension;

class Cache extends AbstractExtension
{
    public static function memcached($host, $port)
    {
        $mem = new \Memcached();
        $mem->addServer($host, $port);
        return $mem;
    }

    public static function redis($host = '127.0.0.1', $port = '6379')
    {
        $redis = new \Redis();
        $redis->connect($host, $port);
        return $redis;
    }
}
