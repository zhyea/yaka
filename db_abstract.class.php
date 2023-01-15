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


//----------------------------------->  表结构和索引相关 end
    /*
    $cond = array('id'=>123, 'group_id'=>array('>'=>100, 'LIKE'=>'\'jack'));
    $s = db_cond_to_sql_add($cond);
    echo $s;

    WHERE id=123 AND group_id>100 AND group_id LIKE '%\'jack%'

    // 格式：
    array('id'=>123, 'group_id'=>123)
    array('id'=>array(1,2,3,4,5))
    array('id'=>array('>' => 100, '<' => 200))
    array('username'=>array('LIKE' => 'jack'))
    */
    function db_cond_to_sql_add($cond): string
    {
        $s = '';
        if (!empty($cond)) {
            $s = ' WHERE ';
            foreach ($cond as $k => $v) {
                if (!is_array($v)) {
                    $v = (is_int($v) || is_float($v)) ? $v : "'" . addslashes($v) . "'";
                    $s .= "`$k`=$v AND ";
                } elseif (isset($v[0])) {
                    // OR 效率比 IN 高
                    $s .= '(';
                    //$v = array_reverse($v);
                    foreach ($v as $v1) {
                        $v1 = (is_int($v1) || is_float($v1)) ? $v1 : "'" . addslashes($v1) . "'";
                        $s .= "`$k`=$v1 OR ";
                    }
                    $s = substr($s, 0, -4);
                    $s .= ') AND ';
                } else {
                    foreach ($v as $k1 => $v1) {
                        if ($k1 == 'LIKE') {
                            $k1 = ' LIKE ';
                            $v1 = "%$v1%";
                        }
                        $v1 = (is_int($v1) || is_float($v1)) ? $v1 : "'" . addslashes($v1) . "'";
                        $s .= "`$k`$k1$v1 AND ";
                    }
                }
            }
            $s = substr($s, 0, -4);
        }
        return $s;
    }


    function db_order_by_to_sql_add($order_by): string
    {
        $s = '';
        if (!empty($order_by)) {
            $s .= ' ORDER BY ';
            $comma = '';
            foreach ($order_by as $k => $v) {
                $s .= $comma . "`$k` " . ($v == 1 ? ' ASC ' : ' DESC ');
                $comma = ',';
            }
        }
        return $s;
    }


    /*
        $arr = array(
            'name'=>'abc',
            'stocks+'=>1,
            'date'=>12345678900,
        )
        db_array_to_update_sql_add($arr);
    */
    function db_array_to_update_sql_add($arr): string
    {
        $s = '';
        foreach ($arr as $k => $v) {
            $v = addslashes($v);
            $op = substr($k, -1);
            if ($op == '+' || $op == '-') {
                $k = substr($k, 0, -1);
                $v = (is_int($v) || is_float($v)) ? $v : "'$v'";
                $s .= "`$k`=$k$op$v,";
            } else {
                $v = (is_int($v) || is_float($v)) ? $v : "'$v'";
                $s .= "`$k`=$v,";
            }
        }
        return substr($s, 0, -1);
    }


    /*
        $arr = array(
            'name'=>'abc',
            'date'=>12345678900,
        )
        db_array_to_insert_sql_add($arr);
    */
    function db_array_to_insert_sql_add($arr): string
    {
        $s = '';
        $keys = array();
        $values = array();
        foreach ($arr as $k => $v) {
            $k = addslashes($k);
            $v = addslashes($v);
            $keys[] = '`' . $k . '`';
            $v = (is_int($v) || is_float($v)) ? $v : "'$v'";
            $values[] = $v;
        }
        $str_key = implode(',', $keys);
        $str_val = implode(',', $values);
        return "($str_key) VALUES ($str_val)";
    }

}