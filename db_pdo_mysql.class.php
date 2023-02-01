<?php

class db_pdo_mysql extends db_abstract_mysql
{


    public function real_connect($host, $user, $password, $name, $charset = '', $engine = '')
    {
        if (strpos($host, ':') !== FALSE) {
            list($host, $port) = explode(':', $host);
        } else {
            $port = 3306;
        }
        try {
            $attr = array(
                PDO::ATTR_TIMEOUT => 5,
                //PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            );
            $link = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $password, $attr);
            //$link->setAttribute(PDO::ATTR_TIMEOUT, 5);
            //$link->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        } catch (Exception $e) {
            $this->error($e->getCode(), '连接数据库服务器失败:' . $e->getMessage());
            return FALSE;
        }
        //$link->setFetchMode(PDO::FETCH_ASSOC);
        $charset and $link->query("SET names $charset, sql_mode=''");
        //$link->query('SET NAMES '.($charset ? $charset.',' : '').', sql_mode=""');
        return $link;
    }


    public function sql_find_one($sql)
    {
        $query = $this->query($sql);
        if (!$query) {
            return $query;
        }
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $r = $query->fetch();
        if ($r === FALSE) {
            // $this->error();
            return NULL;
        }
        return $r;
    }


    public function sql_find($sql, $key = NULL)
    {
        $query = $this->query($sql);
        if (!$query) {
            return $query;
        }
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $arr_list = $query->fetchAll();
        $key and $arr_list = array_list_change_key($arr_list, $key);
        return $arr_list;
    }


    public function query($sql)
    {
        if (!$this->link_read && !$this->connect_slave()) {
            return FALSE;
        }
        $this->link = $this->link_read;
        try {
            $t1 = microtime(1);
            $query = $this->link->query($sql);
            $t2 = microtime(1);

            $t3 = substr($t2 - $t1, 0, 6);
            if (DEBUG and $GLOBALS['gid'] == 1) {
                y_log("[$t3]" . $sql, 'db_sql');
            }
        } catch (Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
            return FALSE;
        }
        if ($query === FALSE) $this->error();
        if (count($this->sql_array) < 1000) $this->sql_array[] = substr($t2 - $t1, 0, 6) . ' ' . $sql;
        return $query;
    }


    public function exec($sql, $link = NULL)
    {
        if (!$this->link_write && !$this->connect_master()) return FALSE;
        $link = $this->link = $this->link_write;
        $n = $t3 = 0;
        try {
            $this->db_create_table($sql);

            $t1 = microtime(1);
            $n = $link->exec($sql); // 返回受到影响的行，插入的 id ?
            $t2 = microtime(1);

            $t3 = substr($t2 - $t1, 0, 6);

            if (DEBUG and $GLOBALS['gid'] == 1) {
                y_log("[$t3]" . $sql, 'db_sql');
            }
        } catch (Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
            return FALSE;
        }

        if (count($this->sql_array) < 1000) {
            $this->sql_array[] = "[$t3]" . $sql;
        }

        if ($n !== FALSE) {
            $pre = strtoupper(substr(trim($sql), 0, 7));
            if ($pre == 'INSERT ' || $pre == 'REPLACE') {
                return $this->last_insert_id();
            }
        } else {
            $this->error();
        }

        return $n;
    }


    public function last_insert_id()
    {
        return $this->link_write->lastinsertid();
    }


    // 设置错误。
    public function error($errno = 0, $err_str = '')
    {
        $error = $this->link ? $this->link->errorInfo() : array(0, $errno, $err_str);
        $this->err_no = $errno ? $errno : ($error[1] ?? 0);
        $this->err_str = $err_str ? $err_str : ($error[2] ?? '');
    }


    public function close(): bool
    {
        $this->link_write = NULL;
        $this->link_read = NULL;
        return TRUE;
    }
}
