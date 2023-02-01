<?php

// 安全过滤字符串，仅仅保留 [a-zA-Z0-9_]
function safe_word($s, $len)
{
    $s = preg_replace('#\W+#', '', $s);
    return substr($s, 0, $len);
}


function str_length($s): int
{
    return mb_strlen($s, 'UTF-8');
}


function sub_string($s, $start, $len): string
{
    return mb_substr($s, $start, $len, 'UTF-8');
}


// txt 转换到 html
function txt_to_html($s)
{
    $s = htmlspecialchars($s);
    $s = str_replace(" ", '&nbsp;', $s);
    $s = str_replace("\t", ' &nbsp; &nbsp; &nbsp; &nbsp;', $s);
    $s = str_replace("\r\n", "\n", $s);
    return str_replace("\n", '<br>', $s);
}


function y_json_encode($data, $pretty = FALSE, $level = 0)
{
    if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    $tab = $pretty ? str_repeat("\t", $level) : '';
    $tab2 = $pretty ? str_repeat("\t", $level + 1) : '';
    $br = $pretty ? "\r\n" : '';
    switch ($type = gettype($data)) {
        case 'NULL':
            return 'null';
        case 'boolean':
            return ($data ? 'true' : 'false');
        case 'integer':
        case 'double':
        case 'float':
            return $data;
        case 'string':
            $data = '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $data) . '"';
            $data = str_replace("\r", '\\r', $data);
            $data = str_replace("\n", '\\n', $data);
            return str_replace("\t", '\\t', $data);
        case 'object':
            $data = get_object_vars($data);
        case 'array':
            $output_index_count = 0;
            $output_indexed = array();
            $output_associative = array();
            foreach ($data as $key => $value) {
                $output_indexed[] = y_json_encode($value, $pretty, $level + 1);
                $output_associative[] = $tab2 . '"' . $key . '":' . y_json_encode($value, $pretty, $level + 1);
                if ($output_index_count !== NULL && $output_index_count++ !== $key) {
                    $output_index_count = NULL;
                }
            }
            if ($output_index_count !== NULL) {
                return '[' . implode(",$br", $output_indexed) . ']';
            } else {
                return "{{$br}" . implode(",$br", $output_associative) . "{$br}{$tab}}";
            }
        default:
            return ''; // Not supported
    }
}


function y_json_decode($json)
{
    $json = trim($json, "\xEF\xBB\xBF");
    $json = trim($json, "\xFE\xFF");
    return json_decode($json, 1);
}


// 判断一个字符串是否在另外一个字符串里面，分隔符 ,
function in_string($s, $str)
{
    if (!$s || !$str) return FALSE;
    $s = ",$s,";
    $str = ",$str,";
    return strpos($str, $s) !== FALSE;
}


// 随机字符
function str_random($n = 16): string
{
    $str = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    $len = str_length($str);
    $return = '';
    for ($i = 0; $i < $n; $i++) {
        $r = mt_rand(1, $len);
        $return .= $str[$r - 1];
    }
    return $return;
}


// 解码客户端提交的 base64 数据
function base64_decode_file_data($data)
{
    if (substr($data, 0, 5) == 'data:') {
        $data = substr($data, strpos($data, ',') + 1);    // 去掉 data:image/png;base64,
    }
    $data = base64_decode($data);
    return $data;
}


function str_push($str, $v, $sep = '_')
{
    if (empty($str)) return $v;
    if (strpos($str, $v . $sep) === FALSE) {
        return $str . $sep . $v;
    }
    return $str;
}