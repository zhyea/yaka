<?php

function cache_new($cfg_cache)
{
    // 缓存初始化，这里并不会产生连接！在真正使用的时候才连接。
    // 这里采用最笨拙的方式而不采用 new $classname 的方式，有利于 opcode 缓存。
    if ($cfg_cache && !empty($cfg_cache['enable'])) {
        switch ($cfg_cache['type']) {
            case 'redis':
                $cache = new cache_redis($cfg_cache['redis']);
                break;
            case 'memcached':
                $cache = new cache_memcached($cfg_cache['memcached']);
                break;
            case 'pdo_mysql':
            case 'mysql':
                $cache = new cache_mysql($cfg_cache['mysql']);
                break;
            case 'xcache':
                $cache = new cache_xcache($cfg_cache['xcache']);
                break;
            case 'apc':
                $cache = new cache_apc($cfg_cache['apc']);
                break;
            case 'yac':
                $cache = new cache_yac($cfg_cache['yac']);
                break;
            default:
                return xn_error(-1, '不支持的 cache type:' . $cfg_cache['type']);
        }
        return $cache;
    }
    return NULL;
}


function cache_get($k, $c = NULL)
{
    $cache = $_SERVER['cache'];
    $c = $c ? $c : $cache;
    if (!$c) {
        return FALSE;
    }
    strlen($k) > 32 and $k = md5($k);

    $k = $c->cachepre . $k;
    return $c->get($k);
}


function cache_set($k, $v, $life = 0, $c = NULL)
{
    $cache = $_SERVER['cache'];
    $c = $c ? $c : $cache;
    if (!$c) {
        return FALSE;
    }

    strlen($k) > 32 and $k = md5($k);

    $k = $c->cachepre . $k;
    return $c->set($k, $v, $life);
}


function cache_delete($k, $c = NULL)
{
    $cache = $_SERVER['cache'];
    $c = $c ? $c : $cache;
    if (!$c) {
        return FALSE;
    }

    strlen($k) > 32 and $k = md5($k);

    $k = $c->cachepre . $k;
    return $c->delete($k);
}


// 尽量避免调用此方法，不会清理保存在 kv 中的数据，逐条 cache_delete() 比较保险
function cache_truncate($c = NULL)
{
    $cache = $_SERVER['cache'];
    $c = $c ? $c : $cache;
    if (!$c) {
        return FALSE;
    }

    return $c->truncate();
}