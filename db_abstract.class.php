<?php

abstract class db_abstract
{
    /**
     * @var array  配置，可以支持主从
     */
    public $conf = array();

    /**
     * @var array 配置，可以支持主从
     */
    public $conf_slave = array();

    /**
     * 写连接
     */
    public $link_write = NULL;

    /**
     *  读连接
     */
    public $link_read = NULL;

    /**
     * 最后一次使用的连接
     */
    public $link = NULL;

    public $err_no = 0;

    public $err_str = '';

    public $sql_array = array();

    public $table_pre = '';


    // p_connect 不释放连接
    public function __destruct()
    {
        if ($this->link_write) {
            $this->link_write = NULL;
        }
        if ($this->link_read) {
            $this->link_read = NULL;
        }
    }

    /**
     * 根据配置文件连接
     */
    public function connect(): bool
    {
        $this->link_write = $this->connect_master();
        $this->link_read = $this->connect_slave();
        return $this->link_write && $this->link_read;
    }


    /**
     * 连接写服务器
     *
     * @return mixed
     */
    public function connect_master()
    {
        if ($this->link_write) {
            return $this->link_write;
        }
        $conf = $this->conf['master'];
        if (!$this->link_write) {
            $this->link_write = $this->real_connect($conf['host'], $conf['user'], $conf['password'], $conf['name'], $conf['charset'], $conf['engine']);
        }
        return $this->link_write;
    }


    /**
     * 连接从服务器，如果有多台，则随机挑选一台，如果为空，则与主服务器一致。
     *
     * @return mixed
     */
    public function connect_slave()
    {
        if ($this->link_read) {
            return $this->link_read;
        }
        if (empty($this->conf['slaves'])) {
            if ($this->link_write === NULL) $this->link_write = $this->connect_master();
            $this->link_read = $this->link_write;
            $this->conf_slave = $this->conf['master'];
        } else {
            //$n = array_rand($this->conf['slaves']);
            $arr = array_rand($this->conf['slaves'], 1);
            $conf = $this->conf['slaves'][$arr[0]];
            $this->conf_slave = $conf;
            $this->link_read = $this->real_connect($conf['host'], $conf['user'], $conf['password'], $conf['name'], $conf['charset'], $conf['engine']);
        }
        return $this->link_read;
    }


    abstract function real_connect($host, $user, $password, $name, $charset = '', $engine = '');


    abstract function sql_find_one($sql);


    abstract function sql_find($sql, $key = NULL);


    abstract function exec($sql, $link = NULL);


    public function version()
    {
        $r = $this->sql_find_one("SELECT VERSION() AS v");
        return $r['v'];
    }

}