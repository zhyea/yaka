<?php

class cache_memcached extends cache_abstract
{


    public $is_memcache = FALSE;

    public function __construct($conf = array())
    {
        if (!extension_loaded('Memcache') && !extension_loaded('Memcached')) {
            $this->error(1, ' Memcached 扩展没有加载，请检查您的 PHP 版本');
            return;
        }
        $this->conf = $conf;
        $this->prefix = $conf['prefix'] ?? 'pre_';
    }

    public function connect()
    {
        $conf = $this->conf;
        if ($this->link) return $this->link;
        if (extension_loaded('Memcache')) {
            $this->is_memcache = TRUE;
            $memcache = new Memcache;
            $r = $memcache->connect($conf['host'], $conf['port']);
        } elseif (extension_loaded('Memcached')) {
            $this->is_memcache = FALSE;
            $memcache = new Memcached;
            $r = $memcache->addserver($conf['host'], $conf['port']);
        } else {
            return $this->error(-1, 'Memcache 扩展不存在。');
        }

        if (!$r) {
            return $this->error(-1, '连接 Memcached 服务器失败。');
        }
        $this->link = $memcache;
        return $this->link;
    }


    public function set($k, $v, $life = 0)
    {
        if (!$this->link && !$this->connect()) {
            return FALSE;
        }
        if ($this->is_memcache) {
            $r = $this->link->set($k, $v, 0, $life);
        } else {
            $r = $this->link->set($k, $v, $life);
        }
        return $r;
    }


    public function get($k)
    {
        if (!$this->link && !$this->connect()) {
            return FALSE;
        }
        $r = $this->link->get($k);
        return $r === FALSE ? NULL : $r;
    }


    public function delete($k)
    {
        if (!$this->link && !$this->connect()) {
            return FALSE;
        }
        return $this->link->delete($k);
    }


    public function truncate()
    {
        if (!$this->link && !$this->connect()) {
            return FALSE;
        }
        return $this->link->flush();
    }


    public function __destruct()
    {
    }
}

