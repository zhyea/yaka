<?php

abstract class db_abstract_mysql extends db_abstract
{

    /**
     * @var bool 优先 InnoDB
     */
    public $innodb_first = TRUE;


    public function __construct($conf)
    {
        $this->conf = $conf;
        $this->table_pre = $conf['master']['table_pre'];
    }


    public function find($table, $cond = array(), $order_by = array(), $page = 1, $page_size = 10, $key = '', $col = array())
    {
        $page = max(1, $page);
        $cond = db_cond_to_sql_add($cond);
        $order_by = db_order_by_to_sql_add($order_by);
        $offset = ($page - 1) * $page_size;
        $cols = $col ? implode(',', $col) : '*';

        return $this->sql_find("SELECT $cols FROM {$this->table_pre}$table $cond$order_by LIMIT $offset,$page_size", $key);
    }


    public function find_one($table, $cond = array(), $order_by = array(), $col = array())
    {
        $cond = db_cond_to_sql_add($cond);
        $order_by = db_order_by_to_sql_add($order_by);
        $cols = $col ? implode(',', $col) : '*';
        return $this->sql_find_one("SELECT $cols FROM {$this->table_pre}$table $cond$order_by LIMIT 1");
    }


    /**
     * 如果为 innodb，条件为空，并且有权限读取 information_schema
     * @param string $table 表名
     * @param array $cond 条件
     * @return int 记录总数
     */
    public function count(string $table, array $cond = array()): int
    {
        $this->connect_slave();
        if (empty($cond) && $this->conf_slave['engine'] == 'innodb') {
            $dbname = $this->conf_slave['name'];
            $sql = "SELECT TABLE_ROWS as num FROM information_schema.tables WHERE TABLE_SCHEMA='$dbname' AND TABLE_NAME='$table'";
        } else {
            $cond = db_cond_to_sql_add($cond);
            $sql = "SELECT COUNT(*) AS num FROM `$table` $cond";
        }
        $arr = $this->sql_find_one($sql);
        return !empty($arr) ? intval($arr['num']) : $arr;
    }


    public function max_id($table, $field, $cond = array()): int
    {
        $sql_add = db_cond_to_sql_add($cond);
        $sql = "SELECT MAX($field) AS max_id FROM `$table` $sql_add";
        $arr = $this->sql_find_one($sql);
        return !empty($arr) ? intval($arr['max_id']) : $arr;
    }


    public function truncate($table)
    {
        return $this->exec("TRUNCATE $table");
    }


    public function is_support_innodb(): bool
    {
        $arr_list = $this->sql_find('SHOW ENGINES');
        $arr_list2 = array_list_key_values($arr_list, 'Engine', 'Support');
        return isset($arr_list2['InnoDB']) and $arr_list2['InnoDB'] == 'YES';
    }


    function db_create_table(&$sql)
    {
        if (strtoupper(substr($sql, 0, 12) == 'CREATE TABLE')) {
            $fulltext = strpos($sql, 'FULLTEXT(') !== FALSE;
            $high_version = version_compare($this->version(), '5.6') >= 0;
            if (!$fulltext || $high_version) {
                $conf = $this->conf['master'];
                if (strtolower($conf['engine']) != 'myisam') {
                    $this->innodb_first and $this->is_support_innodb() and $sql = str_ireplace('MyISAM', 'InnoDB', $sql);
                }
            }
        }
    }
}