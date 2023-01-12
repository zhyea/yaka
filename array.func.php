<?php

function array_value($arr, $key, $default = '')
{
    return $arr[$key] ?? $default;
}


function array_filter_empty($arr)
{
    foreach ($arr as $k => $v) {
        if (empty($v)) unset($arr[$k]);
    }
    return $arr;
}


function array_add_slashes(&$var)
{
    if (is_array($var)) {
        foreach ($var as $k => &$v) {
            array_add_slashes($v);
        }
    } else {
        $var = addslashes($var);
    }
    return $var;
}


function array_strip_slashes(&$var)
{
    if (is_array($var)) {
        foreach ($var as $k => &$v) {
            array_strip_slashes($v);
        }
    } else {
        $var = stripslashes($var);
    }
    return $var;
}


function array_html_special_chars(&$var)
{
    if (is_array($var)) {
        foreach ($var as $k => &$v) {
            array_html_special_chars($v);
        }
    } else {
        $var = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $var);
    }
    return $var;
}


function array_trim(&$var)
{
    if (is_array($var)) {
        foreach ($var as $k => &$v) {
            array_trim($v);
        }
    } else {
        $var = trim($var);
    }
    return $var;
}


// 比较数组的值，如果不相同则保留，以第一个数组为准
function array_diff_value($arr1, $arr2)
{
    foreach ($arr1 as $k => $v) {
        if (isset($arr2[$k]) && $arr2[$k] == $v) unset($arr1[$k]);
    }
    return $arr1;
}


// 对多维数组排序
function array_list_multi_sort($arr_list, $col, $asc = TRUE)
{
    $arr_col = array();
    foreach ($arr_list as $k => $arr) {
        $arr_col[$k] = $arr[$col];
    }
    $asc = $asc ? SORT_ASC : SORT_DESC;
    array_multisort($arr_col, $asc, $arr_list);
    return $arr_list;
}


// 对数组进行查找，排序，筛选，支持多种条件排序
function array_list_cond_order_by($arr_list, $cond = array(), $order_by = array(), $page = 1, $page_size = 20)
{
    $arr_result = array();
    if (empty($arr_list)) return $arr_list;

    // 根据条件，筛选结果
    if ($cond) {
        foreach ($arr_list as $key => $val) {
            $ok = TRUE;
            foreach ($cond as $k => $v) {
                if (!isset($val[$k])) {
                    $ok = FALSE;
                    break;
                }
                if (!is_array($v)) {
                    if ($val[$k] != $v) {
                        $ok = FALSE;
                        break;
                    }
                } else {
                    foreach ($v as $k3 => $v3) {
                        if (
                            ($k3 == '>' && $val[$k] <= $v3) ||
                            ($k3 == '<' && $val[$k] >= $v3) ||
                            ($k3 == '>=' && $val[$k] < $v3) ||
                            ($k3 == '<=' && $val[$k] > $v3) ||
                            ($k3 == '==' && $val[$k] != $v3) ||
                            ($k3 == 'LIKE' && stripos($val[$k], $v3) === FALSE)
                        ) {
                            $ok = FALSE;
                            break 2;
                        }
                    }
                }
            }
            if ($ok) $arr_result[$key] = $val;
        }
    } else {
        $arr_result = $arr_list;
    }

    if ($order_by) {
        $k = key($order_by);
        $v = current($order_by);
        $arr_result = array_list_multi_sort($arr_result, $k, $v == 1);
    }

    $start = ($page - 1) * $page_size;

    return array_assoc_slice($arr_result, $start, $page_size);
}


function array_assoc_slice($arr_list, $start, $length = 0): array
{
    if (isset($arr_list[0])) return array_slice($arr_list, $start, $length);
    $keys = array_keys($arr_list);
    $keys2 = array_slice($keys, $start, $length);
    $result = array();
    foreach ($keys2 as $key) {
        $result[$key] = $arr_list[$key];
    }

    return $result;
}


// 从一个二维数组中取出一个 key=>value 格式的一维数组
function array_list_key_values($arr_list, $key, $value = NULL, $pre = ''): array
{
    $result = array();
    if ($key) {
        foreach ((array)$arr_list as $k => $arr) {
            $result[$pre . $arr[$key]] = $value ? $arr[$value] : $k;
        }
    } else {
        foreach ((array)$arr_list as $arr) {
            $result[] = $arr[$value];
        }
    }
    return $result;
}


// 从一个二维数组中取出一个 values() 格式的一维数组，某一列key
function array_list_values($arr_list, $key): array
{
    if (!$arr_list) return array();
    $return = array();
    foreach ($arr_list as &$arr) {
        $return[] = $arr[$key];
    }
    return $return;
}


// 从一个二维数组中对某一列求和
function array_list_sum($arr_list, $key)
{
    if (!$arr_list) return 0;
    $n = 0;
    foreach ($arr_list as &$arr) {
        $n += $arr[$key];
    }
    return $n;
}


// 从一个二维数组中对某一列求最大值
function array_list_max($arr_list, $key)
{
    if (!$arr_list) return 0;
    $first = array_pop($arr_list);
    $max = $first[$key];
    foreach ($arr_list as &$arr) {
        if ($arr[$key] > $max) {
            $max = $arr[$key];
        }
    }
    return $max;
}


// 从一个二维数组中对某一列求最大值
function array_list_min($arr_list, $key)
{
    if (!$arr_list) return 0;
    $first = array_pop($arr_list);
    $min = $first[$key];
    foreach ($arr_list as &$arr) {
        if ($min > $arr[$key]) {
            $min = $arr[$key];
        }
    }
    return $min;
}


// 将 key 更换为某一列的值，在对多维数组排序后，数字key会丢失，需要此函数
function array_list_change_key($arr_list, $key = '', $pre = ''): array
{
    $result = array();
    if (empty($arr_list)) return $result;
    foreach ($arr_list as &$arr) {
        if (empty($key)) {
            $result[] = $arr;
        } else {
            $result[$pre . '' . $arr[$key]] = $arr;
        }
    }
    return $result;
}

// 保留指定的 key
function array_list_keep_keys($array_list, $keys = array())
{
    !is_array($keys) and $keys = array($keys);
    foreach ($array_list as &$v) {
        $arr = array();
        foreach ($keys as $key) {
            $arr[$key] = $v[$key] ?? NULL;
        }
        $v = $arr;
    }
    return $array_list;
}


// 根据某一列的值进行 chunk
function array_list_chunk($array_list, $key): array
{
    $r = array();
    if (empty($array_list)) return $r;
    foreach ($array_list as &$arr) {
        !isset($r[$arr[$key]]) and $r[$arr[$key]] = array();
        $r[$arr[$key]][] = $arr;
    }
    return $r;
}

