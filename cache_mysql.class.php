<?php

/*
# 持久的 key value 数据存储
DROP TABLE IF EXISTS bbs_kv;
CREATE TABLE bbs_kv (
  k char(32) NOT NULL default '',
  v mediumtext NOT NULL,
  due int(11) unsigned NOT NULL default '0',		# 过期时间
  PRIMARY KEY(k)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
*/

class cache_mysql extends cache_abstract
{

    public $db = NULL;

    public $table = 'cache';


    public function __construct($db_conf = array())
    {
        // 可以复用全局的 $db
        if (is_object($db_conf['db'])) {
            $this->db = $db_conf['db']; // 可以直接传 $db 进来
        } else {
            $this->conf = $db_conf;
            $this->db = db_new($db_conf);
        }
        $this->prefix = $db_conf['prefix'] ?? 'pre_';
    }


    public function connect()
    {
        return db_connect($this->db);
    }


    public function set($k, $v, $life = 0): bool
    {
        $time = time();
        $due = $life ? $time + $life : 0;
        $arr = array(
            'k' => $k,
            'v' => xn_json_encode($v),
            'due' => $due,
        );
        $r = db_replace($this->table, $arr, $this->db);
        if ($r === FALSE) {
            $this->err_no = $this->db->errno;
            $this->err_str = $this->db->errstr;
            return FALSE;
        }
        return true;
    }


    public function get($k)
    {
        $time = time();
        $arr = db_find_one($this->table, array('k' => $k), array(), array(), $this->db);
        // 如果表不存在，则建立表 pre_cache
        if ($arr === FALSE) {
            $this->err_no = $this->db->errno;
            $this->err_str = $this->db->errstr;
            return FALSE;
        }

        if ($arr['due'] && $time > $arr['due']) {
            db_delete($this->table, array('k' => $k), $this->db);
            return NULL;
        }
        return xn_json_decode($arr['v']);
    }


    public function delete($k): bool
    {
        $r = db_delete($this->table, array('k' => $k), $this->db);
        if ($r === FALSE) {
            $this->err_no = $this->db->errno;
            $this->err_str = $this->db->errstr;
            return FALSE;
        }
        return !empty($r);
    }


    public function truncate(): bool
    {
        $r = db_truncate($this->table, $this->db);
        if ($r === FALSE) {
            $this->err_no = $this->db->errno;
            $this->err_str = $this->db->errstr;
            return FALSE;
        }
        return TRUE;
    }


    public function error($err_no = 0, $err_str = '')
    {
        $this->err_no = $err_no;
        $this->err_str = $err_str;
    }


    public function __destruct()
    {
    }
}

