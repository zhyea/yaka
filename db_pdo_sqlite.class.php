<?php


class db_pdo_sqlite extends db_abstract
{


    public function real_connect($host, $user, $password, $name, $charset = '', $engine = '')
    {
        $db_sqlite = "sqlite:$host";
        try {
            $attr = array(
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            );
            $link = new PDO($db_sqlite, $attr);//连接sqlite
            //new PDO($sqlitedb,'','',$attr);//连接sqlite
        } catch (Exception $e) {
            $this->error($e->getCode(), '连接数据库服务器失败:' . $e->getMessage());
            return FALSE;
        }
        //$link->setFetchMode(PDO::FETCH_ASSOC);
        return $link;

    }


    public function sql_find_one($sql)
    {
        $query = $this->query($sql);
        if (!$query) return $query;
        $query->setFetchMode(PDO::FETCH_ASSOC);
        return $query->fetch();
    }


    public function sql_find($sql, $key = NULL)
    {
        $query = $this->query($sql);
        if (!$query) return $query;
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $arr_list = $query->fetchAll();
        $key and $arr_list = array_list_change_key($arr_list, $key);
        return $arr_list;
    }


    public function query($sql)
    {
        if (!$this->link_read && !$this->connect_slave()) return FALSE;
        $this->link = $this->link_read;
        $query = $this->link->query($sql);
        if ($query === FALSE) {
            $this->error();
        }

        if (count($this->sql_array) < 1000) {
            $this->sql_array[] = $sql;
        }

        return $query;
    }


    public function exec($sql, $link = NULL)
    {
        if (!$this->link_write && !$this->connect_master()) return FALSE;
        $link = $this->link = $this->link_write;
        $n = $link->exec($sql); // 返回受到影响的行，插入的 id ?
        if (count($this->sql_array) < 1000) $this->sql_array[] = $sql;
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
        $error = $this->link ? $this->link->errorInfo() : array(0, 0, '');
        $this->err_no = $errno ? $errno : ($error[1] ?? 0);
        $this->err_str = $err_str ? $err_str : ($error[2] ?? '');
        DEBUG and trigger_error('Database Error:' . $this->err_str);
    }
}
