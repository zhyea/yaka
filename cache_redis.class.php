<?php

class cache_redis extends cache_abstract
{


    public function __construct($conf = array())
    {
        if (!extension_loaded('Redis')) {
            $this->error(-1, ' Redis 扩展没有加载');
            return;
        }
        $this->conf = $conf;
        $this->prefix = $conf['prefix'] ?? 'pre_';
    }


    public function connect()
    {
        if ($this->link) {
            return $this->link;
        }
        $redis = new Redis;
        $r = $redis->connect($this->conf['host'], $this->conf['port']);
        if (!$r) {
            return $this->error(-1, '连接 Redis 服务器失败。');
        }
        //$redis->select('xn');
        $this->link = $redis;
        return $this->link;
    }


    public function set($k, $v, $life = 0)
    {
        if (!$this->link && !$this->connect()) {
            return FALSE;
        }
        $v = yaka_json_encode($v);
        $r = $this->link->set($k, $v);
        $life and $r and $this->link->expire($k, $life);
        return $r;
    }


    public function get($k)
    {
        if (!$this->link && !$this->connect()) {
            return FALSE;
        }
        $r = $this->link->get($k);
        return $r === FALSE ? NULL : xn_json_decode($r);
    }


    public function delete($k): bool
    {
        if (!$this->link && !$this->connect()) {
            return FALSE;
        }
        return (bool)$this->link->del($k);
    }


    public function truncate(): bool
    {
        if (!$this->link && !$this->connect()) {
            return FALSE;
        }
        return $this->link->flushdb();
    }


    public function __destruct()
    {
    }
}

