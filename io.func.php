<?php

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
function rmdir_recursive($dir, $keep_dir = 0)
{
    if ($dir == '/' || $dir == './' || $dir == '../') return FALSE;// 不允许删除根目录，避免程序意外删除数据。
    if (!is_dir($dir)) return FALSE;

    substr($dir, -1) != '/' and $dir .= '/';

    $files = glob($dir . '*'); // +glob($dir.'.*')
    foreach (glob($dir . '.*') as $v) {
        if (substr($v, -1) != '.' && substr($v, -2) != '..') $files[] = $v;
    }
    $file_arr = $dir_arr = array();
    if ($files) {
        foreach ($files as $file) {
            if (is_dir($file)) {
                $dir_arr[] = $file;
            } else {
                $file_arr[] = $file;
            }
        }
    }
    if ($file_arr) {
        foreach ($file_arr as $file) {
            yaka_unlink($file);
        }
    }
    if ($dir_arr) {
        foreach ($dir_arr as $file) {
            rmdir_recursive($file);
        }
    }
    if (!$keep_dir) yaka_rmdir($dir);
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


function yaka_rmdir($dir): bool
{
    return is_dir($dir) && rmdir($dir);
}


function yaka_unlink($file): bool
{
    return is_file($file) && unlink($file);
}


function file_mtime($file)
{
    return is_file($file) ? filemtime($file) : 0;
}


/*
	实例：
	set_dir(123, APP_PATH.'upload');

	000/000/1.jpg
	000/000/100.jpg
	000/000/100.jpg
	000/000/999.jpg
	000/001/1000.jpg
	000/001/001.jpg
	000/002/001.jpg
*/
function set_dir($id, $dir = './'): string
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
function get_dir($id): string
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


function move_upload_file($src_file, $dest_file): bool
{
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
            $tmp_file = $file . uniqid() . '.tmp';
            $r = touch($tmp_file);
            if (!$r) return FALSE;
            if (!is_file($tmp_file)) return FALSE;
            yaka_unlink($tmp_file);
            return TRUE;
        } else {
            return FALSE;
        }
    }
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
        $arr = yaka_json_decode($s);
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
