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
     * @var string 写连接
     */
    public $link_write = NULL;

    /**
     * @var string 读连接
     */
    public $link_read = NULL;

    /**
     * @var string 最后一次使用的连接
     */
    public $link = NULL;

    public $err_no = 0;

    public $err_str = '';

    public $sql_array = array();

    public $table_pre = '';
}