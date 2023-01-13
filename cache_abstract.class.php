<?php


abstract class cache_abstract
{

    public $conf = array();

    public $link = NULL;

    public $prefix = '';

    public $err_no = 0;

    public $err_str = '';


    public function connect()
    {
    }


    abstract function set($k, $v, $life);


    abstract  function get($k);


    abstract function delete($k);


    abstract function truncate();


    public function error($err_no = 0, $err_str = '')
    {
        $this->err_no = $err_no;
        $this->err_str = $err_str;
        DEBUG and trigger_error('Cache Error:' . $this->err_str);

        return NULL;
    }

}