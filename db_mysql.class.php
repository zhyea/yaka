<?php

class db_mysql extends db_abstract_mysql
{


    public function real_connect($host, $user, $password, $name, $charset = '', $engine = '')
    {
        $link = @mysqli_connect($host, $user, $password); // 如果用户名相同，则返回同一个连接。 fastcgi 持久连接更省资源
        if (!$link) {
            $this->error(mysqli_errno($link), '连接数据库服务器失败:' . mysqli_error($link));
            return FALSE;
        }
        if (!mysqli_select_db($link, $name)) {
            $this->error(mysqli_errno($link), '选择数据库失败:' . mysqli_error($link));
            return FALSE;
        }
        $charset and $this->query("SET names $charset, sql_mode=''", $link);
        return $link;
    }


    public function sql_find_one($sql)
    {
        $query = $this->query($sql);
        if (!$query) {
            return $query;
        }
        // 如果结果为空，返回 FALSE
        $r = mysqli_fetch_assoc($query);
        if ($r === FALSE) {
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
        $result = array();
        while ($arr = mysqli_fetch_assoc($query)) {
            $key ? $result[$arr[$key]] = $arr : $result[] = $arr;
            // 顺序没有问题，尽管是数字，仍然是有序的，看来内部实现是链表，与 js 数组不同。
        }
        return $result;
    }


    public function exec($sql, $link = NULL)
    {
        if (!$link) {
            if (!$this->link_write && !$this->connect_master()) {
                return FALSE;
            }
            $this->link = $this->link = $this->link_write;
        }

        $this->db_create_table($sql);

        $t1 = microtime(1);
        $query = mysqli_query($this->link_write, $sql);
        $t2 = microtime(1);
        $t3 = substr($t2 - $t1, 0, 6);

        DEBUG and yaka_log("[$t3]" . $sql, 'db_sql');
        if (count($this->sql_array) < 1000) $this->sql_array[] = "[$t3]" . $sql;

        if ($query !== FALSE) {
            $pre = strtoupper(substr(trim($sql), 0, 7));
            if ($pre == 'INSERT ' || $pre == 'REPLACE') {
                return mysqli_insert_id($this->link_write);
            } elseif ($pre == 'UPDATE ' || $pre == 'DELETE ') {
                return mysqli_affected_rows($this->link_write);
            }
        } else {
            $this->error();
        }

        return $query;
    }


    public function query($sql, $link = NULL)
    {
        if (!$link) {
            if (!$this->link_read && !$this->connect_slave()) return FALSE;;
            $link = $this->link = $this->link_read;
        }
        $t1 = microtime(1);
        $query = mysqli_query($link, $sql);
        $t2 = microtime(1);
        if ($query === FALSE) {
            $this->error();
        }

        $t3 = substr($t2 - $t1, 0, 6);
        DEBUG and yaka_log("[$t3]" . $sql, 'db_sql');
        if (count($this->sql_array) < 1000) {
            $this->sql_array[] = "[$t3]" . $sql;
        }

        return $query;
    }


    public function close(): bool
    {
        $r = mysqli_close($this->link_write);
        if ($this->link_write != $this->link_read) {
            $r = mysqli_close($this->link_read);
        }
        return $r;
    }


    public function error($err_no = 0, $err_str = '')
    {
        $this->err_no = $err_no ? $err_no : ($this->link ? mysqli_errno($this->link) : mysqli_errno());
        $this->err_str = $err_str ? $err_str : ($this->link ? mysqli_error($this->link) : mysqli_error());
        DEBUG and trigger_error('Database Error:' . $this->err_str);
    }
}