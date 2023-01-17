<?php


// 使用全局变量记录错误信息
function yaka_error($no, $str, $return = FALSE)
{
    global $err_no, $err_str;
    $err_no = $no;
    $err_str = $str;
    return $return;
}


function lang($key, $arr = array())
{
    $lang = $_SERVER['lang'];
    if (!isset($lang[$key])) {
        return 'lang[' . $key . ']';
    }
    $s = $lang[$key];
    if (!empty($arr)) {
        foreach ($arr as $k => $v) {
            $s = str_replace('{' . $k . '}', $v, $s);
        }
    }
    return $s;
}


// ---------------------> encrypt function end

function pagination_tpl($url, $text, $active = '')
{
    global $g_pagination_tpl;
    empty($g_pagination_tpl) and $g_pagination_tpl = '<li class="page-item{active}"><a href="{url}" class="page-link">{text}</a></li>';
    return str_replace(array('{url}', '{text}', '{active}'), array($url, $text, $active), $g_pagination_tpl);
}


// bootstrap 翻页，命名与 bootstrap 保持一致
function pagination($url, $total_num, $page, $page_size = 20)
{
    $total_page = ceil($total_num / $page_size);
    if ($total_page < 2) {
        return '';
    }
    $page = min($total_page, $page);
    $show_num = 5;    // 显示多少个页 * 2

    $start = max(1, $page - $show_num);
    $end = min($total_page, $page + $show_num);

    // 不足 $shownum，补全左右两侧
    $right = $page + $show_num - $total_page;
    $right > 0 && $start = max(1, $start -= $right);
    $left = $page - $show_num;
    $left < 0 && $end = min($total_page, $end -= $left);

    $s = '';
    $page != 1 && $s .= pagination_tpl(str_replace('{page}', $page - 1, $url), '◀', '');
    if ($start > 1) $s .= pagination_tpl(str_replace('{page}', 1, $url), '1 ' . ($start > 2 ? '...' : ''));
    for ($i = $start; $i <= $end; $i++) {
        $s .= pagination_tpl(str_replace('{page}', $i, $url), $i, $i == $page ? ' active' : '');
    }
    if ($end != $total_page) $s .= pagination_tpl(str_replace('{page}', $total_page, $url), ($total_page - $end > 1 ? '...' : '') . $total_page);
    $page != $total_page && $s .= pagination_tpl(str_replace('{page}', $page + 1, $url), '▶');
    return $s;
}


// 简单的上一页，下一页，比较省资源，不用count(), 推荐使用，命名与 bootstrap 保持一致
function pager($url, $total_num, $page, $page_size = 20): string
{
    $total_page = ceil($total_num / $page_size);
    if ($total_page < 2) {
        return '';
    }
    $page = min($total_page, $page);

    $s = '';
    $page > 1 and $s .= '<li><a href="' . str_replace('{page}', $page - 1, $url) . '">上一页</a></li>';
    $s .= " $page / $total_page ";
    $total_num >= $page_size and $page != $total_page and $s .= '<li><a href="' . str_replace('{page}', $page + 1, $url) . '">下一页</a></li>';
    return $s;
}


function mid($n, $min, $max)
{
    if ($n < $min) {
        return $min;
    }
    if ($n > $max) {
        return $max;
    }
    return $n;
}

function humandate($timestamp, $lan = array())
{
    $time = $_SERVER['time'];
    $lang = $_SERVER['lang'];

    $seconds = $time - $timestamp;
    $lan = empty($lang) ? $lan : $lang;
    empty($lan) and $lan = array(
        'month_ago' => '月前',
        'day_ago' => '天前',
        'hour_ago' => '小时前',
        'minute_ago' => '分钟前',
        'second_ago' => '秒前',
    );
    if ($seconds > 31536000) {
        return date('Y-n-j', $timestamp);
    } elseif ($seconds > 2592000) {
        return floor($seconds / 2592000) . $lan['month_ago'];
    } elseif ($seconds > 86400) {
        return floor($seconds / 86400) . $lan['day_ago'];
    } elseif ($seconds > 3600) {
        return floor($seconds / 3600) . $lan['hour_ago'];
    } elseif ($seconds > 60) {
        return floor($seconds / 60) . $lan['minute_ago'];
    } else {
        return $seconds . $lan['second_ago'];
    }
}


function humannumber($num)
{
    $num > 100000 && $num = ceil($num / 10000) . '万';
    return $num;
}


function humansize($num)
{
    if ($num > 1073741824) {
        return number_format($num / 1073741824, 2, '.', '') . 'G';
    } elseif ($num > 1048576) {
        return number_format($num / 1048576, 2, '.', '') . 'M';
    } elseif ($num > 1024) {
        return number_format($num / 1024, 2, '.', '') . 'K';
    } else {
        return $num . 'B';
    }
}


// 不安全的获取 IP 方式，在开启 CDN 的时候，如果被人猜到真实 IP，则可以伪造。
function ip()
{
    $conf = _SERVER('conf');
    $ip = '127.0.0.1';
    if (empty($conf['cdn_on'])) {
        $ip = _SERVER('REMOTE_ADDR');
    } else {
        if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENTIP'])) {
            $ip = $_SERVER['HTTP_CLIENTIP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $arr = array_filter(explode(',', $ip));
            $ip = trim(end($arr));
        } else {
            $ip = _SERVER('REMOTE_ADDR');
        }
    }
    return long2ip(ip2long($ip));
}


// 日志记录
function yaka_log($s, $file = 'error')
{
    if (DEBUG == 0 && strpos($file, 'error') === FALSE) return;
    $time = $_SERVER['time'];
    $ip = $_SERVER['ip'];
    $conf = _SERVER('conf');
    $uid = intval(G('uid')); // xiunophp 未定义 $uid
    $day = date('Ym', $time); // 按照月存放，否则 Ymd 目录太多。
    $mtime = date('Y-m-d H:i:s'); // 默认值为 time()
    $url = $_SERVER['REQUEST_URI'] ?? '';
    $logpath = $conf['log_path'] . $day;
    !is_dir($logpath) and mkdir($logpath, 0777, true);

    $s = str_replace(array("\r\n", "\n", "\t"), ' ', $s);
    $s = "<?php exit;?>\t$mtime\t$ip\t$url\t$uid\t$s\r\n";

    @error_log($s, 3, $logpath . "/$file.php");
}


function yaka_shutdown_handle()
{
}

function yaka_debug_info()
{
    $db = $_SERVER['db'];
    $start_time = $_SERVER['starttime'];
    $s = '';
    if (DEBUG > 1) {
        $s .= '<fieldset class="fieldset small debug break-all">';
        $s .= '<p>Processed Time:' . (microtime(1) - $start_time) . '</p>';
        if (IN_CMD) {
            foreach ($db->sqls as $sql) {
                $s .= "$sql\r\n";
            }
        } else {
            $s .= "\r\n<ul>\r\n";
            foreach ($db->sqls as $sql) {
                $s .= "<li>$sql</li>\r\n";
            }
            $s .= "</ul>\r\n";
            $s .= '_REQUEST:<br>';
            $s .= txt_to_html(print_r($_REQUEST, 1));
            if (!empty($_SESSION)) {
                $s .= '_SESSION:<br>';
                $s .= txt_to_html(print_r($_SESSION, 1));
            }
            $s .= '';
        }
        $s .= '</fieldset>';
    }
    return $s;
}


function y2f($rmb)
{
    return floor($rmb * 10 * 10);
}


// $round: float round ceil floor
function f2y($rmb, $round = 'float')
{
    $rmb = floor($rmb * 100) / 10000;
    if ($round == 'float') {
        $rmb = number_format($rmb, 2, '.', '');
    } elseif ($round == 'round') {
        $rmb = round($rmb);
    } elseif ($round == 'ceil') {
        $rmb = ceil($rmb);
    } elseif ($round == 'floor') {
        $rmb = floor($rmb);
    }
    return $rmb;
}


// 无 Notice 方式的获取超级全局变量中的 key
function _GET($k, $def = NULL)
{
    return $_GET[$k] ?? $def;
}

function _POST($k, $def = NULL)
{
    return $_POST[$k] ?? $def;
}

function _COOKIE($k, $def = NULL)
{
    return $_COOKIE[$k] ?? $def;
}

function _REQUEST($k, $def = NULL)
{
    return $_REQUEST[$k] ?? $def;
}

function _ENV($k, $def = NULL)
{
    return $_ENV[$k] ?? $def;
}

function _SERVER($k, $def = NULL)
{
    return $_SERVER[$k] ?? $def;
}

function GLOBALS($k, $def = NULL)
{
    return $GLOBALS[$k] ?? $def;
}

function G($k, $def = NULL)
{
    return $GLOBALS[$k] ?? $def;
}

function _SESSION($k, $def = NULL)
{
    global $g_session;
    return $_SESSION[$k] ?? ($g_session[$k] ?? $def);
}
