<?php

// 此处的 $db 是局部变量，要注意，它返回后在定义为全局变量，可以有多个实例。
function db_new($conf)
{
    global $err_no, $err_str;
    // 数据库初始化，这里并不会产生连接！
    if ($conf) {
        // 代码不仅仅是给人看的，更重要的是给编译器分析的，不要玩 $db = new $dbclass()，那样不利于优化和 opcache 。
        switch ($conf['type']) {
            case 'mysql':
                $db = new db_mysql($conf['mysql']);
                break;
            case 'pdo_mysql':
                $db = new db_pdo_mysql($conf['pdo_mysql']);
                break;
            case 'pdo_sqlite':
                $db = new db_pdo_sqlite($conf['pdo_sqlite']);
                break;
            default:
                return xn_error(-1, 'Not supported db type:' . $conf['type']);
        }
        if (!$db || $db->errstr) {
            $err_no = -1;
            $err_str = $db->errstr;
            return FALSE;
        }
        return $db;
    }
    return NULL;
}


// 测试连接
function db_connect($d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;

    $r = $d->connect();

    db_error($r, $d);

    return $r;
}


function db_close($d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    $r = $d->close();

    db_error($r, $d);

    return $r;
}


function db_sql_find_one($sql, $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }
    $arr = $d->sql_find_one($sql);

    db_error($arr, $d, $sql);

    return $arr;
}


function db_sql_find($sql, $key = NULL, $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }
    $arr = $d->sql_find($sql, $key);

    db_error($arr, $d, $sql);

    return $arr;
}

// 如果为 INSERT 或者 REPLACE，则返回 mysql_insert_id();
// 如果为 UPDATE 或者 DELETE，则返回 mysql_affected_rows();
// 对于非自增的表，INSERT 后，返回的一直是 0
// 判断是否执行成功: mysql_exec() === FALSE
function db_exec($sql, $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    DEBUG and yaka_log($sql, 'db_exec');

    $n = $d->exec($sql);

    db_error($n, $d, $sql);

    return $n;
}


function db_count($table, $cond = array(), $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    $r = $d->count($d->tablepre . $table, $cond);

    db_error($r, $d);

    return $r;
}


function db_max_id($table, $field, $cond = array(), $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    $r = $d->maxid($d->tablepre . $table, $field, $cond);

    db_error($r, $d);

    return $r;
}


// NO SQL 封装，可以支持 MySQL Maria PG MongoDB
function db_create($table, $arr, $d = NULL): bool
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    return db_insert($table, $arr);
}


function db_insert($table, $arr, $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    $sql_add = db_array_to_insert_sql_add($arr);
    if (!$sql_add) {
        return FALSE;
    }
    return db_exec("INSERT INTO {$d->tablepre}$table $sql_add", $d);
}

function db_replace($table, $arr, $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    $sql_add = db_array_to_insert_sql_add($arr);
    if (!$sql_add) {
        return FALSE;
    }
    return db_exec("REPLACE INTO {$d->tablepre}$table $sql_add", $d);
}


function db_update($table, $cond, $update, $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    $cond_add = db_cond_to_sql_add($cond);
    $sql_add = db_array_to_update_sql_add($update);
    if (!$sql_add) {
        return FALSE;
    }
    return db_exec("UPDATE {$d->tablepre}$table SET $sql_add $cond_add", $d);
}


function db_delete($table, $cond, $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    $cond_add = db_cond_to_sql_add($cond);
    return db_exec("DELETE FROM {$d->tablepre}$table $cond_add", $d);
}


function db_truncate($table, $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    return $d->truncate($d->tablepre . $table);
}

function db_read($table, $cond, $d = NULL)
{
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    $sql_add = db_cond_to_sql_add($cond);
    $sql = "SELECT * FROM {$d->tablepre}$table $sql_add";
    return db_sql_find_one($sql, $d);
}


function db_find($table, $cond = array(), $order_by = array(), $page = 1, $page_size = 10, $key = '', $col = array(), $d = NULL)
{
    $db = $_SERVER['db'];

    // 高效写法，定参有利于编译器优化
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    return $d->find($table, $cond, $order_by, $page, $page_size, $key, $col);
}


function db_find_one($table, $cond = array(), $order_by = array(), $col = array(), $d = NULL)
{
    $db = $_SERVER['db'];

    // 高效写法，定参有利于编译器优化
    $d = $d ? $d : $db;
    if (!$d) {
        return FALSE;
    }

    return $d->find_one($table, $cond, $order_by, $col);
}


// 保存 $db 错误到全局
function db_error($r, $d = NULL, $sql = '')
{
    global $err_no, $err_str;
    if ($r === FALSE) { //  && $d->errno != 0
        $err_no = $d->errno;
        $err_str = db_err_str_safe($err_no, $d->errstr);
        $s = 'SQL:' . $sql . "\r\nerr_no: " . $err_no . ", err_str: " . $err_str;
        yaka_log($s, 'db_error');
    }
}


// 安全的错误信息
function db_err_str_safe($err_no, $err_str): string
{
    if (DEBUG) {
        return $err_str;
    }
    if ($err_no == 1049) {
        return '数据库名不存在，请手工创建';
    } elseif ($err_no == 2003) {
        return '连接数据库服务器失败，请检查IP是否正确，或者防火墙设置';
    } elseif ($err_no == 1024) {
        return '连接数据库失败';
    } elseif ($err_no == 1045) {
        return '数据库账户密码错误';
    }
    return $err_str;
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
