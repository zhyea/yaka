<?php

function yaka_message($code, $message)
{
    $ajax = $_SERVER['ajax'];
    echo $ajax ? yaka_json_encode(array('code' => $code, 'message' => $message)) : $message;
    exit;
}


function log_post_data()
{
    $method = $_SERVER['method'];
    if ($method != 'POST') return;
    $post = $_POST;
    isset($post['password']) and $post['password'] = '******';        // 干掉密码信息
    isset($post['password_new']) and $post['password_new'] = '******';    // 干掉密码信息
    isset($post['password_old']) and $post['password_old'] = '******';    // 干掉密码信息

    yaka_log(yaka_json_encode($post), 'post_data');
}


// 中断流程很危险！可能会导致数据问题，线上模式不允许中断流程！
function error_handle($errno, $errstr, $errfile, $errline)
{

    // PHP 内部默认处理
    if (DEBUG == 0) return FALSE;

    // 如果放在 register_shutdown_function 里面，文件句柄会被关闭，然后这里就写入不了文件了！
    $time = $_SERVER['time'];
    $ajax = $_SERVER['ajax'];
    IN_CMD and $errstr = str_replace('<br>', "\n", $errstr);

    $subject = "Error[$errno]: $errstr, File: $errfile, Line: $errline";
    $message = array();
    yaka_log($subject, 'php_error'); // 所有PHP错误报告都记录日志

    $arr = debug_backtrace();
    array_shift($arr);
    foreach ($arr as $v) {
        $args = '';
        if (!empty($v['args']) && is_array($v['args'])) foreach ($v['args'] as $v2) $args .= ($args ? ' , ' : '') . (is_array($v2) ? 'array(' . count($v2) . ')' : (is_object($v2) ? 'object' : $v2));
        !isset($v['file']) and $v['file'] = '';
        !isset($v['line']) and $v['line'] = '';
        $message [] = "File: $v[file], Line: $v[line], $v[function]($args) ";
    }
    $txt = $subject . "\r\n" . implode("\r\n", $message);
    $html = $s = "<fieldset class=\"fieldset small notice\">
			<b>$subject</b>
			<div>" . implode("<br>\r\n", $message) . "</div>
		</fieldset>";
    echo ($ajax || IN_CMD) ? $txt : $html;
    DEBUG == 2 and yaka_log($txt, 'debug_error');
    return TRUE;
}


// 使用全局变量记录错误信息
function yaka_error($no, $str, $return = FALSE)
{
    global $err_no, $err_str;
    $err_no = $no;
    $err_str = $str;
    return $return;
}


function param($key, $def_val = '', $html_special_chars = TRUE, $add_slashes = FALSE)
{
    if (!isset($_REQUEST[$key]) || ($key === 0 && empty($_REQUEST[$key]))) {
        if (is_array($def_val)) {
            return array();
        } else {
            return $def_val;
        }
    }
    $val = $_REQUEST[$key];
    return param_force($val, $def_val, $html_special_chars, $add_slashes);
}


// 安全获取单词类参数
function param_word($key, $len = 32)
{
    $s = param($key);
    return safe_word($s, $len);
}


function param_base64($key, $len = 0)
{
    $s = param($key, '', FALSE);
    if (empty($s)) {
        return '';
    }
    $s = substr($s, strpos($s, ',') + 1);
    $s = base64_decode($s);
    $len and $s = substr($s, 0, $len);
    return $s;
}


function param_json($key)
{
    $s = param($key, '', FALSE);
    if (empty($s)) {
        return '';
    }
    return xn_json_decode($s);
}


function param_url($key): string
{
    $s = param($key, '', FALSE);
    return url_decode($s);
}


// 安全过滤字符串，仅仅保留 [a-zA-Z0-9_]
function safe_word($s, $len)
{
    $s = preg_replace('#\W+#', '', $s);
    return substr($s, 0, $len);
}


function param_force($val, $def_val, $html_special_chars = TRUE, $add_slashes = FALSE)
{
    $get_magic_quotes_gpc = _SERVER('get_magic_quotes_gpc');
    if (is_array($def_val)) {
        $def_val = empty($def_val) ? '' : $def_val[0]; // 数组的第一个元素，如果没有则为空字符串
        if (is_array($val)) {
            foreach ($val as &$v) {
                if (is_array($v)) {
                    $v = $def_val;
                } else {
                    if (is_string($def_val)) {
                        //$v = trim($v);
                        $add_slashes and !$get_magic_quotes_gpc && $v = addslashes($v);
                        !$add_slashes and $get_magic_quotes_gpc && $v = stripslashes($v);
                        $html_special_chars and $v = htmlspecialchars($v);
                    } else {
                        $v = intval($v);
                    }
                }
            }
        } else {
            return array();
        }
    } else {
        if (is_array($val)) {
            $val = $def_val;
        } else {
            if (is_string($def_val)) {
                //$val = trim($val);
                $add_slashes and !$get_magic_quotes_gpc && $val = addslashes($val);
                !$add_slashes and $get_magic_quotes_gpc && $val = stripslashes($val);
                $html_special_chars and $val = htmlspecialchars($val);
            } else {
                $val = intval($val);
            }
        }
    }
    return $val;
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


function jump($message, $url = '', $delay = 3)
{
    $ajax = $_SERVER['ajax'];
    if ($ajax) {
        return $message;
    }
    if (!$url) {
        return $message;
    }
    $url == 'back' and $url = 'javascript:history.back()';
    $html_add = '<script>setTimeout(function() {window.location=\'' . $url . '\'}, ' . ($delay * 1000) . ');</script>';
    return '<a href="' . $url . '">' . $message . '</a>' . $html_add;
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


function url_encode($s)
{
    $s = urlencode($s);
    $s = str_replace('_', '_5f', $s);
    $s = str_replace('-', '_2d', $s);
    $s = str_replace('.', '_2e', $s);
    $s = str_replace('+', '_2b', $s);
    $s = str_replace('=', '_3d', $s);
    return str_replace('%', '_', $s);
}


function url_decode($s): string
{
    $s = str_replace('_', '%', $s);
    return urldecode($s);
}


function yaka_json_encode($data, $pretty = FALSE, $level = 0)
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
                $output_indexed[] = yaka_json_encode($value, $pretty, $level + 1);
                $output_associative[] = $tab2 . '"' . $key . '":' . yaka_json_encode($value, $pretty, $level + 1);
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

function xn_json_decode($json)
{
    $json = trim($json, "\xEF\xBB\xBF");
    $json = trim($json, "\xFE\xFF");
    return json_decode($json, 1);
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

/*
	中国国情下的判断浏览器类型，简直就是五代十国，乱七八糟，对博主的收集表示感谢

	参考：
	http://www.cnblogs.com/wangchao928/p/4166805.html
	http://www.useragentstring.com/pages/Internet%20Explorer/
	https://github.com/serbanghita/Mobile-Detect/blob/master/Mobile_Detect.php

	Mozilla/4.0 (compatible; MSIE 5.0; Windows NT)
	Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)
	Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.2)
	Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)

	Win7+ie9：
	Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 2.0.50727; SLCC2; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C; Tablet PC 2.0; .NET4.0E)

	win7+ie11，模拟 78910 头是一样的
	mozilla/5.0 (windows nt 6.1; wow64; trident/7.0; rv:11.0) like gecko

	Win7+ie8：
	Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; InfoPath.3)

	WinXP+ie8：
	Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; GTB7.0)

	WinXP+ie7：
	Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)

	WinXP+ie6：
	Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)

	傲游3.1.7在Win7+ie9,高速模式:
	Mozilla/5.0 (Windows; U; Windows NT 6.1; ) AppleWebKit/534.12 (KHTML, like Gecko) Maxthon/3.0 Safari/534.12

	傲游3.1.7在Win7+ie9,IE内核兼容模式:
	Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C; .NET4.0E)

	搜狗
	搜狗3.0在Win7+ie9,IE内核兼容模式:
	Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C; .NET4.0E; SE 2.X MetaSr 1.0)

	搜狗3.0在Win7+ie9,高速模式:
	Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.472.33 Safari/534.3 SE 2.X MetaSr 1.0

	360
	360浏览器3.0在Win7+ie9:
	Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C; .NET4.0E)

	QQ 浏览器
	QQ 浏览器6.9(11079)在Win7+ie9,极速模式:
	Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.41 Safari/535.1 QQBrowser/6.9.11079.201

	QQ浏览器6.9(11079)在Win7+ie9,IE内核兼容模式:
	Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C; .NET4.0E) QQBrowser/6.9.11079.201

	阿云浏览器
	阿云浏览器 1.3.0.1724 Beta 在Win7+ie9:
	Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)

	MIUI V5
	Mozilla/5.0 (Linux; U; Android <android-version>; <location>; <MODEL> Build/<ProductLine>) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30 XiaoMi/MiuiBrowser/1.0
*/
function get__browser()
{
    // 默认为 chrome 标准浏览器
    $browser = array(
        'device' => 'pc', // pc|mobile|pad
        'name' => 'chrome', // chrome|firefox|ie|opera
        'version' => 30,
    );
    $agent = _SERVER('HTTP_USER_AGENT');
    // 主要判断是否为垃圾 IE6789
    if (strpos($agent, 'msie') !== FALSE || stripos($agent, 'trident') !== FALSE) {
        $browser['name'] = 'ie';
        $browser['version'] = 8;
        preg_match('#msie\s*([\d\.]+)#is', $agent, $m);
        if (!empty($m[1])) {
            if (strpos($agent, 'compatible; msie 7.0;') !== FALSE) {
                $browser['version'] = 8;
            } else {
                $browser['version'] = intval($m[1]);
            }
        } else {
            // 匹配兼容模式 Trident/7.0，兼容模式下会有此标志 $trident = 7;
            preg_match('#Trident/([\d\.]+)#is', $agent, $m);
            if (!empty($m[1])) {
                $trident = intval($m[1]);
                $trident == 4 and $browser['version'] = 8;
                $trident == 5 and $browser['version'] = 9;
                $trident > 5 and $browser['version'] = 10;
            }
        }
    }

    if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap") || stripos($agent, 'phone') || stripos($agent, 'mobile') || strpos($agent, 'ipod'))) {
        $browser['device'] = 'mobile';
    } elseif (strpos($agent, 'pad') !== FALSE) {
        $browser['device'] = 'pad';
        $browser['name'] = '';
        $browser['version'] = '';
        /*
        } elseif(strpos($agent, 'miui') !== FALSE) {
            $browser['device'] = 'mobile';
            $browser['name'] = 'xiaomi';
            $browser['version'] = '';
        */
    } else {
        $robots = array('bot', 'spider', 'slurp');
        foreach ($robots as $robot) {
            if (strpos($agent, $robot) !== FALSE) {
                $browser['name'] = 'robot';
                return $browser;
            }
        }
    }
    return $browser;
}


function check_browser($browser)
{
    if ($browser['name'] == 'ie' && $browser['version'] < 8) {
        include _include(APP_PATH . 'view/htm/browser.htm');
        exit;
    }
}

function is_robot()
{
    $agent = _SERVER('HTTP_USER_AGENT');
    $robots = array('bot', 'spider', 'slurp');
    foreach ($robots as $robot) {
        if (strpos($agent, $robot) !== FALSE) {
            return TRUE;
        }
    }
    return FALSE;
}

function browser_lang()
{
    // return 'zh-cn';
    $accept = _SERVER('HTTP_ACCEPT_LANGUAGE');
    $accept = substr($accept, 0, strpos($accept, ';'));
    if (strpos($accept, 'ko-kr') !== FALSE) {
        return 'ko-kr';
        // } elseif(strpos($accept, 'en') !== FALSE) {
        // 	return 'en';
    } else {
        return 'zh-cn';
    }
}

// 安全请求一个 URL
// ini_set('default_socket_timeout', 60);
function http_get($url, $cookie = '', $timeout = 30, $times = 3)
{
    //return '';
//	$arr = array(
//			'ssl' => array (
//			'verify_peer'   => TRUE,
//			'cafile'        => './cacert.pem',
//			'verify_depth'  => 5,
//			'method'  	=> 'GET',
//			'timeout'  	=> $timeout,
//			'CN_match'      => 'secure.example.com'
//		)
//	);
    if (substr($url, 0, 8) == 'https://') {
        return https_get($url, $cookie, $timeout, $times);
    }
    $arr = array(
        'http' => array(
            'method' => 'GET',
            'timeout' => $timeout
        )
    );
    $stream = stream_context_create($arr);
    while ($times-- > 0) {
        $s = file_get_contents($url, NULL, $stream, 0, 4096000);
        if ($s !== FALSE) return $s;
    }
    return FALSE;
}


function http_post($url, $post = '', $cookie = '', $timeout = 30, $times = 3)
{
    if (substr($url, 0, 8) == 'https://') {
        return https_post($url, $post, $cookie, $timeout, $times);
    }
    is_array($post) and $post = http_build_query($post);
    is_array($cookie) and $cookie = http_build_query($cookie);
    $stream = stream_context_create(array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\nx-requested-with: XMLHttpRequest\r\nCookie: $cookie\r\n", 'method' => 'POST', 'content' => $post, 'timeout' => $timeout)));
    while ($times-- > 0) {
        $s = file_get_contents($url, NULL, $stream, 0, 4096000);
        if ($s !== FALSE) return $s;
    }
    return FALSE;
}


function https_get($url, $cookie = '', $timeout = 30, $times = 1)
{
    if (substr($url, 0, 7) == 'http://') {
        return http_get($url, $cookie, $timeout, $times);
    }
    return https_post($url, '', $cookie, $timeout, $times, 'GET');
}


function https_post($url, $post = '', $cookie = '', $timeout = 30, $times = 1, $method = 'POST')
{
    if (substr($url, 0, 7) == 'http://') {
        return http_post($url, $post, $cookie, $timeout, $times);
    }
    is_array($post) and $post = http_build_query($post);
    is_array($cookie) and $cookie = http_build_query($cookie);
    $w = stream_get_wrappers();
    $allow_url_fopen = strtolower(ini_get('allow_url_fopen'));
    $allow_url_fopen = (empty($allow_url_fopen) || $allow_url_fopen == 'off') ? 0 : 1;
    if (extension_loaded('openssl') && in_array('https', $w) && $allow_url_fopen) {
        $stream = stream_context_create(array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\nx-requested-with: XMLHttpRequest\r\nCookie: $cookie\r\n", 'method' => $method, 'content' => $post, 'timeout' => $timeout)));
        $s = file_get_contents($url, NULL, $stream, 0, 4096000);
        return $s;
    } elseif (!function_exists('curl_init')) {
        return yaka_error(-1, 'server not installed curl.');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 2); // 1/2
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded', 'x-requested-with: XMLHttpRequest'));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, _SERVER('HTTP_USER_AGENT'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在，默认可以省略
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $header = array('Content-type: application/x-www-form-urlencoded', 'X-Requested-With: XMLHttpRequest');
    if ($cookie) {
        $header[] = "Cookie: $cookie";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    (!ini_get('safe_mode') && !ini_get('open_basedir')) && curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转, 安全模式不允许
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        return yaka_error(-1, 'Errno' . curl_error($ch));
    }
    if (!$data) {
        curl_close($ch);
        return '';
    }

    list($header, $data) = explode("\r\n\r\n", $data);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code == 301 || $http_code == 302) {
        $matches = array();
        preg_match('/Location:(.*?)\n/', $header, $matches);
        $url = trim(array_pop($matches));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = curl_exec($ch);
    }
    curl_close($ch);
    return $data;
}


// 多线程抓取数据，需要CURL支持，一般在命令行下执行，此函数收集于互联网，由 xiuno 整理，经过测试，会导致 CPU 100%。
function http_multi_get($urls)
{
    // 如果不支持，则转为单线程顺序抓取
    $data = array();
    if (!function_exists('curl_multi_init')) {
        foreach ($urls as $k => $url) {
            $data[$k] = https_get($url);
        }
        return $data;
    }

    $multi_handle = curl_multi_init();
    foreach ($urls as $i => $url) {
        $conn[$i] = curl_init($url);
        curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
        $timeout = 3;
        curl_setopt($conn[$i], CURLOPT_CONNECTTIMEOUT, $timeout); // 超时 seconds
        curl_setopt($conn[$i], CURLOPT_FOLLOWLOCATION, 1);
        //curl_easy_setopt(curl, CURLOPT_NOSIGNAL, 1);
        curl_multi_add_handle($multi_handle, $conn[$i]);
    }
    do {
        $mrc = curl_multi_exec($multi_handle, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    while ($active and $mrc == CURLM_OK) {
        if (curl_multi_select($multi_handle) != -1) {
            do {
                $mrc = curl_multi_exec($multi_handle, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }
    foreach ($urls as $i => $url) {
        $data[$i] = curl_multi_getcontent($conn[$i]);
        curl_multi_remove_handle($multi_handle, $conn[$i]);
        curl_close($conn[$i]);
    }
    return $data;
}


// 将变量写入到文件，根据后缀判断文件格式，先备份，再写入，写入失败，还原备份
function file_replace_var($filepath, $replace = array(), $pretty = FALSE)
{
    $ext = file_ext($filepath);
    if ($ext == 'php') {
        $arr = include $filepath;
        $arr = array_merge($arr, $replace);
        $s = "<?php\r\nreturn " . var_export($arr, true) . ";\r\n?>";
        // 备份文件
        file_backup($filepath);
        $r = file_put_contents_try($filepath, $s);
        $r != str_length($s) ? file_backup_restore($filepath) : file_backup_unlink($filepath);
        return $r;
    } elseif ($ext == 'js' || $ext == 'json') {
        $s = file_get_contents_try($filepath);
        $arr = xn_json_decode($s);
        if (empty($arr)) return FALSE;
        $arr = array_merge($arr, $replace);
        $s = yaka_json_encode($arr, $pretty);
        file_backup($filepath);
        $r = file_put_contents_try($filepath, $s);
        $r != str_length($s) ? file_backup_restore($filepath) : file_backup_unlink($filepath);
        return $r;
    }
}

function file_backname($filepath): string
{
    $dirname = dirname($filepath);
    //$filename = file_name($filepath);
    $file_pre = file_pre($filepath);
    $file_ext = file_ext($filepath);
    return "$file_pre.backup.$file_ext";
}


function is_backfile($filepath): bool
{
    return strpos($filepath, '.backup.') !== FALSE;
}


// 备份文件
function file_backup($filepath): bool
{
    $back_file = file_backname($filepath);
    if (is_file($back_file)) return TRUE; // 备份已经存在
    $r = yaka_copy($filepath, $back_file);
    clearstatcache();
    return $r && filesize($back_file) == filesize($filepath);
}


// 还原备份
function file_backup_restore($filepath)
{
    $back_file = file_backname($filepath);
    $r = yaka_copy($back_file, $filepath);
    clearstatcache();
    $r && filesize($back_file) == filesize($filepath) && yaka_unlink($back_file);
    return $r;
}


// 删除备份
function file_backup_unlink($filepath)
{
    $back_file = file_backname($filepath);
    return yaka_unlink($back_file);
}


function file_get_contents_try($file, $times = 3)
{
    while ($times-- > 0) {
        $fp = fopen($file, 'rb');
        if ($fp) {
            $size = filesize($file);
            if ($size == 0) return '';
            $s = fread($fp, $size);
            fclose($fp);
            return $s;
        } else {
            sleep(1);
        }
    }
    return FALSE;
}


function file_put_contents_try($file, $s, $times = 3)
{
    while ($times-- > 0) {
        $fp = fopen($file, 'wb');
        if ($fp and flock($fp, LOCK_EX)) {
            $n = fwrite($fp, $s);
            version_compare(PHP_VERSION, '5.3.2', '>=') and flock($fp, LOCK_UN);
            fclose($fp);
            clearstatcache();
            return $n;
        } else {
            sleep(1);
        }
    }
    return FALSE;
}


// 判断一个字符串是否在另外一个字符串里面，分隔符 ,
function in_string($s, $str)
{
    if (!$s || !$str) return FALSE;
    $s = ",$s,";
    $str = ",$str,";
    return strpos($str, $s) !== FALSE;
}


function move_upload_file($src_file, $dest_file)
{
    //$r = move_uploaded_file($srcfile, $destfile);
    return yaka_copy($src_file, $dest_file);
}


// 文件后缀名，不包含 .
function file_ext($filename, $max = 16)
{
    $ext = strtolower(substr(strrchr($filename, '.'), 1));
    $ext = url_encode($ext);
    str_length($ext) > $max and $ext = substr($ext, 0, $max);
    if (!preg_match('#^\w+$#', $ext)) $ext = 'attach';
    return $ext;
}


// 文件的前缀，不包含最后一个 .
function file_pre($filename, $max = 32)
{
    return substr($filename, 0, strrpos($filename, '.'));
}


// 获取路径中的文件名
function file_name($path)
{
    return substr($path, strrpos($path, '/') + 1);
}


// 获取 http://xxx.com/path/
function http_url_path()
{
    $port = _SERVER('SERVER_PORT');
    //$portadd = ($port == 80 ? '' : ':'.$port);
    $host = _SERVER('HTTP_HOST');  // host 里包含 port
    $https = strtolower(_SERVER('HTTPS', 'off'));
    $proto = strtolower(_SERVER('HTTP_X_FORWARDED_PROTO'));
    $path = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
    $http = (($port == 443) || $proto == 'https' || ($https && $https != 'off')) ? 'https' : 'http';
    return "$http://$host$path/";
}

/**
 * URL format: http://www.domain.com/demo/?user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/?user-login.htm&a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm&a=b&c=d
 * array(
 *     0 => user,
 *     1 => login
 *     a => b
 *     c => d
 * )
 */
function yaka_url_parse($request_url)
{
    // 处理: /demo/?user-login.htm?a=b&c=d
    // 结果：/demo/user-login.htm?a=b&c=d
    $request_url = str_replace('/?', '/', $request_url);
    $arr = parse_url($request_url);

    $q = array_value($arr, 'path');
    $pos = strrpos($q, '/');
    $pos === FALSE && $pos = -1;
    $q = substr($q, $pos + 1); // 截取最后一个 / 后面的内容
    // 查找第一个 ? & 进行分割
    $sep = strpos($q, '?') === FALSE ? strpos($q, '&') : FALSE;
    if ($sep !== FALSE) {
        // 对后半部分截取，并且分析
        $front = substr($q, 0, $sep);
        $behind = substr($q, $sep + 1);
    } else {
        $front = $q;
        $behind = '';
    }

    if (substr($front, -4) == '.htm') $front = substr($front, 0, -4);
    $r = $front ? (array)explode('-', $front) : array();

    // 将后半部分合并
    $arr1 = $arr2 = $arr3 = array();
    $behind and parse_str($behind, $arr1);

    // 将 xxx.htm?a=b&c=d 放到后面，并且修正 $_GET
    if (!empty($arr['query'])) {
        parse_str($arr['query'], $arr2);
    } else {
        !empty($_GET) and $_GET = array();
    }
    $arr3 = $arr1 + $arr2;
    if ($arr3) {
        //array_diff_key($arr3, $_GET) || array_diff_key($_GET, $arr3);
        count($arr3) != count($_GET) and $_GET = $arr3;
    } else {
        !empty($_GET) and $_GET = array();
    }
    $r += $arr3;

    $_SERVER['REQUEST_URI_NO_PATH'] = substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '/') + 1);

    // 是否开启 /user/login 这种格式的 URL
    $conf = _SERVER('conf');
    if (!empty($conf['url_rewrite_on']) && $conf['url_rewrite_on'] == 3) {
        $r = url_parse_path_format($_SERVER['REQUEST_URI']) + $r;
    }

    isset($r[0]) and $r[0] == 'index.php' and $r[0] = 'index';
    return $r;
}


// 将参数添加到 URL
function url_add_arg($url, $k, $v): string
{
    $pos = strpos($url, '.htm');
    if ($pos === FALSE) {
        return strpos($url, '?') === FALSE ? $url . "&$k=$v" : $url . "?$k=$v";
    } else {
        return substr($url, 0, $pos) . '-' . $v . substr($url, $pos);
    }
}

/**
 * 支持 URL format: http://www.domain.com/user/login?a=1&b=2
 * array(
 *     0 => user,
 *     1 => login,
 *     a => 1,
 *     b => 2
 * )
 */
function url_parse_path_format($s)
{
    $get = array();
    substr($s, 0, 1) == '/' and $s = substr($s, 1);
    $arr = explode('/', $s);
    $get = $arr;
    $last = array_pop($arr);
    if (strpos($last, '?') !== FALSE) {
        $get = $arr;
        $arr1 = explode('?', $last);
        parse_str($arr1[1], $arr2);
        $get[] = $arr1[0];
        $get = array_merge($get, $arr2);
    }
    return $get;
}

// 递归遍历目录
function glob_recursive($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
    }
    return $files;
}

// 递归删除目录，这个函数比较危险，传参一定要小心
function rmdir_recusive($dir, $keepdir = 0)
{
    if ($dir == '/' || $dir == './' || $dir == '../') return FALSE;// 不允许删除根目录，避免程序意外删除数据。
    if (!is_dir($dir)) return FALSE;

    substr($dir, -1) != '/' and $dir .= '/';

    $files = glob($dir . '*'); // +glob($dir.'.*')
    foreach (glob($dir . '.*') as $v) {
        if (substr($v, -1) != '.' && substr($v, -2) != '..') $files[] = $v;
    }
    $filearr = $dirarr = array();
    if ($files) {
        foreach ($files as $file) {
            if (is_dir($file)) {
                $dirarr[] = $file;
            } else {
                $filearr[] = $file;
            }
        }
    }
    if ($filearr) {
        foreach ($filearr as $file) {
            yaka_unlink($file);
        }
    }
    if ($dirarr) {
        foreach ($dirarr as $file) {
            rmdir_recusive($file);
        }
    }
    if (!$keepdir) yaka_rmdir($dir);
    return TRUE;
}


function yaka_copy($src, $dest): bool
{
    return is_file($src) && copy($src, $dest);
}


function yaka_mkdir($dir, $mod = NULL, $recursive = NULL): bool
{
    return !is_dir($dir) && mkdir($dir, $mod, $recursive);
}


function yaka_rmdir($dir)
{
    return is_dir($dir) && rmdir($dir);
}


function yaka_unlink($file)
{
    return is_file($file) && unlink($file);
}


function file_mtime($file)
{
    return is_file($file) ? filemtime($file) : 0;
}


/*
	实例：
	xn_set_dir(123, APP_PATH.'upload');
	
	000/000/1.jpg
	000/000/100.jpg
	000/000/100.jpg
	000/000/999.jpg
	000/001/1000.jpg
	000/001/001.jpg
	000/002/001.jpg
*/
function set_dir($id, $dir = './')
{

    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    $dir1 = $dir . $s1;
    $dir2 = $dir . "$s1/$s2";

    !is_dir($dir1) && mkdir($dir1, 0777);
    !is_dir($dir2) && mkdir($dir2, 0777);
    return "$s1/$s2";
}


// 取得路径：001/123
function get_dir($id)
{
    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    return "$s1/$s2";
}

// 递归拷贝目录
function copy_recursive($src, $dst)
{
    substr($src, -1) == '/' and $src = substr($src, 0, -1);
    substr($dst, -1) == '/' and $dst = substr($dst, 0, -1);
    $dir = opendir($src);
    !is_dir($dst) and mkdir($dst);
    while (FALSE !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copy_recursive($src . '/' . $file, $dst . '/' . $file);
            } else {
                yaka_copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// 随机字符
function str_random($n = 16)
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


// 检测文件是否可写，兼容 windows
function is_writable($file)
{

    if (PHP_OS != 'WINNT') {
        return is_writable($file);
    } else {
        // 如果是 windows，比较麻烦，这也只是大致检测，不够精准。
        if (is_file($file)) {
            $fp = fopen($file, 'a+');
            if (!$fp) return FALSE;
            fclose($fp);
            return TRUE;
        } elseif (is_dir($file)) {
            $tmpfile = $file . uniqid() . '.tmp';
            $r = touch($tmpfile);
            if (!$r) return FALSE;
            if (!is_file($tmpfile)) return FALSE;
            yaka_unlink($tmpfile);
            return TRUE;
        } else {
            return FALSE;
        }
    }
}


function xn_shutdown_handle()
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


// 解码客户端提交的 base64 数据
function base64_decode_file_data($data)
{
    if (substr($data, 0, 5) == 'data:') {
        $data = substr($data, strpos($data, ',') + 1);    // 去掉 data:image/png;base64,
    }
    $data = base64_decode($data);
    return $data;
}


// 输出
function http_404()
{
    header('HTTP/1.1 404 Not Found');
    header('Status: 404 Not Found');
    echo '<h1>404 Not Found</h1>';
    exit;
}


// 无权限访问
function http_403()
{
    header('HTTP/1.1 403 Forbidden');
    header('Status: 403 Forbidden');
    echo '<h1>403 Forbidden</h1>';
    exit;
}

function http_location($url)
{
    header('Location:' . $url);
    exit;
}


// 获取 referer
function http_referer()
{
    $len = str_length(http_url_path());
    $referer = param('referer');
    empty($referer) and $referer = _SERVER('HTTP_REFERER');
    $referer2 = substr($referer, $len);
    if (strpos($referer, url('user-login')) !== FALSE || strpos($referer, url('user-logout')) !== FALSE || strpos($referer, url('user-create')) !== FALSE) {
        $referer = './';
    }
    // 安全过滤，只支持站内跳转，不允许跳到外部，否则可能会被 XSS
    // $referer = str_replace('\'', '', $referer);
    if (!preg_match('#^\\??[\w\-/]+\.htm$#', $referer2) && !preg_match('#^[\w\/]*$#', $referer2)) {
        $referer = './';
    }
    return $referer;
}


function str_push($str, $v, $sep = '_')
{
    if (empty($str)) return $v;
    if (strpos($str, $v . $sep) === FALSE) {
        return $str . $sep . $v;
    }
    return $str;
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
