<?php


function y_key()
{
    $conf = _SERVER('conf');
    return $conf['auth_key'] ?? '';
}


// 安全的加密 key，过期时间 100 秒，如果最后 2 位 大于 90，则
// 临时使用，一般用作数据传输和校验
function safe_key(): string
{
    global $conf, $long_ip, $time, $useragent;
    $conf = _SERVER('conf');
    $long_ip = _SERVER('longip');
    $time = _SERVER('time');
    $useragent = _SERVER('useragent');
    $key = y_key();
    $behind = intval(substr($time, -2, 2));
    $t = $behind > 80 ? $time - 20 : ($behind < 20 ? $time - 40 : $time); // 修正范围，防止进位，有效时间窗口
    $front = substr($t, 0, -2);
    return md5($key . $useragent . $front);
}


function y_encrypt($txt, $key = '')
{
    empty($key) and $key = y_key();
    $encrypt = _y_encrypt($txt, $key);
    return url_encode(base64_encode($encrypt));
}


function y_decrypt($txt, $key = '')
{
    empty($key) and $key = y_key();
    $encrypt = base64_decode(url_decode($txt));
    return _y_decrypt($encrypt, $key);
}


// ---------------------> encrypt function
function _y_long2str($v, $w)
{
    $len = count($v);
    $n = ($len - 1) << 2;
    if ($w) {
        $m = $v[$len - 1];
        if (($m < $n - 3) || ($m > $n)) return FALSE;
        $n = $m;
    }
    $s = array();
    for ($i = 0; $i < $len; $i++) {
        $s[$i] = pack("V", $v[$i]);
    }
    if ($w) {
        return substr(join('', $s), 0, $n);
    } else {
        return join('', $s);
    }
}

function _y_str2long($s, $w)
{
    $v = unpack("V*", $s . str_repeat("\0", (4 - str_length($s) % 4) & 3));
    $v = array_values($v);
    if ($w) {
        $v[count($v)] = str_length($s);
    }
    return $v;
}

function _y_int32($n): int
{
    while ($n >= 2147483648) $n -= 4294967296;
    while ($n <= -2147483649) $n += 4294967296;
    return (int)$n;
}


function _y_encrypt($str, $key)
{
    if ($str == '') {
        return '';
    }
    $v = _y_str2long($str, TRUE);
    $k = _y_str2long($key, FALSE);
    if (count($k) < 4) {
        for ($i = count($k); $i < 4; $i++) {
            $k[$i] = 0;
        }
    }
    $n = count($v) - 1;

    $z = $v[$n];
    $y = $v[0];
    $delta = 0x9E3779B9;
    $q = floor(6 + 52 / ($n + 1));
    $sum = 0;
    while (0 < $q--) {
        $sum = _y_int32($sum + $delta);
        $e = $sum >> 2 & 3;
        for ($p = 0; $p < $n; $p++) {
            $y = $v[$p + 1];
            $mx = _y_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ _y_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$p] = _y_int32($v[$p] + $mx);
        }
        $y = $v[0];
        $mx = _y_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ _y_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
        $z = $v[$n] = _y_int32($v[$n] + $mx);
    }
    return _y_long2str($v, FALSE);
}


function _y_decrypt($str, $key)
{
    if ($str == '') {
        return '';
    }
    $v = _y_str2long($str, FALSE);
    $k = _y_str2long($key, FALSE);
    if (count($k) < 4) {
        for ($i = count($k); $i < 4; $i++) {
            $k[$i] = 0;
        }
    }
    $n = count($v) - 1;

    $z = $v[$n];
    $y = $v[0];
    $delta = 0x9E3779B9;
    $q = floor(6 + 52 / ($n + 1));
    $sum = _y_int32($q * $delta);
    while ($sum != 0) {
        $e = $sum >> 2 & 3;
        for ($p = $n; $p > 0; $p--) {
            $z = $v[$p - 1];
            $mx = _y_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ _y_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y = $v[$p] = _y_int32($v[$p] - $mx);
        }
        $z = $v[$n];
        $mx = _y_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ _y_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
        $y = $v[0] = _y_int32($v[0] - $mx);
        $sum = _y_int32($sum - $delta);
    }
    return _y_long2str($v, TRUE);
}
