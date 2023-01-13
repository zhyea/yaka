<?php

class cache_apc extends cache_abstract
{


    public function __construct($conf = array())
    {
        if (!function_exists('apc_fetch')) {
            $this->error(-1, 'APC 扩展没有加载，请检查您的 PHP 版本');
            return;
        }
        $this->conf = $conf;
        $this->prefix = $conf['prefix'] ?? 'pre_';
    }


    public function set($k, $v, $life)
    {
        return apc_store($k, $v, $life);
    }


    public function get($k)
    {
        $r = apc_fetch($k);
        if ($r === FALSE){ $r = NULL;}
        return $r;
    }


    public function delete($k)
    {
        return apc_delete($k);
    }


    public function truncate()
    {
        return apc_clear_cache('user');
    }


    public function __destruct()
    {
    }
}

