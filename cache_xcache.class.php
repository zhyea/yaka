<?php

// 经过测试 xcache3.1 xcache_set() life 参数不管用

class cache_xcache extends cache_abstract
{

    public function __construct($conf = array())
    {
        if (!function_exists('xcache_set')) {
            $this->error(1, 'Xcache 扩展没有加载，请检查您的 PHP 版本');
            return;
        }
        $this->conf = $conf;
        $this->prefix = $conf['prefix'] ?? 'pre_';
    }


    public function set($k, $v, $life)
    {
        if (function_exists('xcache_set')) {
            return xcache_set($k, $v, $life);
        }
        return NULL;
    }


    // 取不到数据的时候返回 NULL，不是 FALSE
    public function get($k)
    {
        if (function_exists('xcache_get')) {
            $r = xcache_get($k);
            if ($r === FALSE) {
                $r = NULL;
            }
            return $r;
        }
        return NULL;
    }


    public function delete($k)
    {
        if (function_exists('xcache_unset')) {
            return xcache_unset($k);
        }
        return NULL;
    }


    public function truncate(): bool
    {

        if (function_exists('xcache_unset_by_prefix')) {
            xcache_unset_by_prefix($this->prefix);
            return TRUE;
        }
        return FALSE;
    }


    public function __destruct()
    {
    }
}
