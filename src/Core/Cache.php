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

    public static function getRedisInstance($host = '127.0.0.1', $port = '6379')
    {
        $redis = new \Redis();
        $redis->connect($host, $port);
        return $redis;
    }
}
