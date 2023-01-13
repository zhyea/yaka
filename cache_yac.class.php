<?php

class cache_yac extends cache_abstract
{

    public $yac = NULL;


    public function __construct($conf = array())
    {
        if (!class_exists('Yac')) {
            $this->error(1, 'yac 扩展没有加载，请检查您的 PHP 版本');
            return;
        }
        $this->prefix = $conf['prefix'] ?? 'pre_';
        $this->yac = new Yac($this->prefix);
    }


    public function set($k, $v, $life)
    {
        return $this->yac->set($k, $v, $life);
    }


    // 取不到数据的时候返回 NULL，不是 FALSE
    public function get($k)
    {
        $r = $this->yac->get($k);
        if ($r === FALSE) {
            $r = NULL;
        }
        return $r;
    }


    public function delete($k)
    {
        return $this->yac->delete($k);
    }


    public function truncate()
    {
        $this->yac->flush();
        return TRUE;
    }


    public function __destruct()
    {
    }
}
