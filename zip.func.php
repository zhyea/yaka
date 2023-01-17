<?php

function yaka_zip($zip_file, $ext_dir)
{
    $path_info = pathinfo($ext_dir);
    $parent_path = $path_info['dirname'];
    $dirname = $path_info['basename'];

    yaka_unlink($zip_file);
    $z = new ZipArchive();
    $z->open($zip_file, ZIPARCHIVE::CREATE);
    $z->addEmptyDir($dirname);
    yaka_dir_to_zip($z, $ext_dir, str_length("$parent_path/"));
    $z->close();
}


function yaka_unzip($zip_file, $ext_dir)
{
    $z = new ZipArchive;
    if ($z->open($zip_file) === TRUE) {
        $z->extractTo($ext_dir);
        $z->close();
    }

    // 如果解压出来多了一层，则去掉一层。
    // /path/dir1/dir1/a/b   ->   /path/dir1/a/b
    $ext_dir_last = substr(strrchr(substr($ext_dir, 0, -1), '/'), 1);
    if (is_dir($ext_dir . $ext_dir_last)) { // /path/dir1/dir1
        $ext_dir_tmp = substr($ext_dir, 0, -1) . '__yaka__tmp__dir__/';

        rename(substr($ext_dir, 0, -1), substr($ext_dir_tmp, 0, -1));
        rename($ext_dir_tmp . $ext_dir_last, substr($ext_dir, 0, -1));

        // 干掉临时目录
        rmdir($ext_dir_tmp);
    }
}


function yaka_dir_to_zip($z, $zip_path, $pre_len = 0)
{
    substr($zip_path, -1) != '/' and $zip_path .= '/';
    $file_list = glob($zip_path . "*");
    foreach ($file_list as $filepath) {
        $local_path = substr($filepath, $pre_len);
        if (is_file($filepath)) {
            $z->addFile($filepath, $local_path);
        } elseif (is_dir($filepath)) {
            $z->addEmptyDir($local_path);
            yaka_dir_to_zip($z, $filepath, $pre_len);
        }
    }
}


// 第一层的目录名称，用来兼容多层打包
function yaka_zip_unwrap_path($zip_path, $dirname = ''): array
{
    substr($zip_path, -1) != '/' and $zip_path .= '/';
    $arr = glob("$zip_path*");
    if (empty($arr)) {
        return array($zip_path, '');
    }
    $arr[0] = str_replace('\\', '/', $arr[0]);
    $tmp_arr = explode('/', $arr[0]);
    $wrap_dir = array_pop($tmp_arr);
    $last_path = $arr[0] . '/';
    if (!$dirname) {
        return count($arr) == 1 ? array($last_path, $wrap_dir) : array($zip_path, '');
    }
    if ($dirname == $wrap_dir) {
        return array($last_path, $wrap_dir);
    } else {
        return array($zip_path, '');
    }
}
