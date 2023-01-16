<?php

// 安全缩略，按照ID存储
/*
	$arr = image_safe_thumb('abc.jpg', 123, '.jpg', './upload/', 100, 100);
	array(
		'filesize'=>1234,
		'width'=>100,
		'height'=>100,
		'file_url' => '001/0123/1233.jpg'
	);
*/

// 不包含 .
function image_ext($filename): string
{
    return strtolower(substr(strrchr($filename, '.'), 1));
}


// 获取安全的文件名，如果文件存在，则加时间戳和随机数，避免重复
function image_safe_name($filename, $dir): string
{
    $time = $_SERVER['time'];
    // 最后一个 . 保留，其他的 . 替换
    $s1 = substr($filename, 0, strrpos($filename, '.'));
    $s2 = substr(strrchr($filename, '.'), 1);
    $s1 = preg_replace('#\W#', '_', $s1);
    $s2 = preg_replace('#\W#', '_', $s2);
    if (is_file($dir . "$s1.$s2")) {
        $new_name = $s1 . $time . rand(1, 1000) . '.' . $s2;
    } else {
        $new_name = "$s1.$s2";
    }
    return $new_name;
}


// 缩略图的名字
function image_thumb_name($filename): string
{
    return substr($filename, 0, strrpos($filename, '.')) . '_thumb' . strrchr($filename, '.');
}


// 随即文件名
function image_rand_name($k): string
{
    $time = $_SERVER['time'];
    return $time . '_' . rand(1000000000, 9999999999) . '_' . $k;
}


/*
	实例：
	image_set_dir(123, './upload');

	000/000/1.jpg
	000/000/100.jpg
	000/000/100.jpg
	000/000/999.jpg
	000/001/1000.jpg
	000/001/001.jpg
	000/002/001.jpg
*/
function image_set_dir($id, $dir): string
{

    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    $dir = $dir . "$s1/$s2";
    !is_dir($dir) && mkdir($dir, 0777, TRUE);

    return "$s1/$s2";
}


// 取得 user home 路径
function image_get_dir($id): string
{
    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    return "$s1/$s2";
}

/*
	实例：
 	image_thumb('xxx.jpg', 'xxx_thumb.jpg', 200, 200);

 	返回：
 	array('filesize'=>0, 'width'=>0, 'height'=>0)
 */
function image_thumb($src_file, $dest_file, $forced_width = 80, $forced_height = 80): array
{
    $return = array('filesize' => 0, 'width' => 0, 'height' => 0);
    $des_text = image_ext($dest_file);
    if (!in_array($des_text, array('gif', 'jpg', 'bmp', 'png'))) {
        return $return;
    }

    $img_info = getimagesize($src_file);
    $src_width = $img_info[0];
    $src_height = $img_info[1];
    if ($src_width == 0 || $src_height == 0) {
        return $return;
    }

    if (!function_exists('imagecreatefromjpeg')) {
        copy($src_file, $dest_file);
        return array('filesize' => filesize($dest_file), 'width' => $src_width, 'height' => $src_height);
    }

    // 按规定比例缩略
    $src_scale = $src_width / $src_height;
    $des_scale = $forced_width / $forced_height;
    if ($src_width <= $forced_width && $src_height <= $forced_height) {
        $des_width = $src_width;
        $des_height = $src_height;
    } elseif ($src_scale >= $des_scale) {
        $des_width = ($src_width >= $forced_width) ? $forced_width : $src_width;
        $des_height = $des_width / $src_scale;
        $des_height = ($des_height >= $forced_height) ? $forced_height : $des_height;
    } else {
        $des_height = ($src_height >= $forced_height) ? $forced_height : $src_height;
        $des_width = $des_height * $src_scale;
        $des_width = ($des_width >= $forced_width) ? $forced_width : $des_width;
    }

    switch ($img_info['mime']) {
        case 'image/jpeg':
            $img_src = imagecreatefromjpeg($src_file);
            !$img_src && $img_src = imagecreatefromgif($src_file);
            break;
        case 'image/gif':
            $img_src = imagecreatefromgif($src_file);
            !$img_src && $img_src = imagecreatefromjpeg($src_file);
            break;
        case 'image/png':
            $img_src = imagecreatefrompng($src_file);
            break;
        case 'image/wbmp':
            $img_src = imagecreatefromwbmp($src_file);
            break;
        default :
            return $return;
    }

    if (!$img_src) return $return;

    $img_dst = imagecreatetruecolor($des_width, $des_height);
    imagefill($img_dst, 0, 0, 0xFFFFFF);
    imagecopyresampled($img_dst, $img_src, 0, 0, 0, 0, $des_width, $des_height, $src_width, $src_height);

    $conf = _SERVER('conf');
    $tmp_path = $conf['tmp_path'] ?? ini_get('upload_tmp_dir') . '/';
    $tmp_path == '/' and $tmp_path = './tmp/';

    $tmp_file = $tmp_path . md5($dest_file) . '.tmp';
    switch ($des_text) {
        case 'jpg':
            imagejpeg($img_dst, $tmp_file, 90);
            break;
        case 'gif':
            imagegif($img_dst, $tmp_file);
            break;
        case 'png':
            imagepng($img_dst, $tmp_file);
            break;
    }
    $r = array('filesize' => filesize($tmp_file), 'width' => $des_width, 'height' => $des_height);;
    copy($tmp_file, $dest_file);
    is_file($tmp_file) && unlink($tmp_file);
    imagedestroy($img_dst);

    return $r;
}


/**
 * 图片裁切
 *
 * @param string $src_file 原图片路径(绝对路径/abc.jpg)
 * @param string $dest_file 裁切后生成新名称(绝对路径/rename.jpg)
 * @param int $clip_x 被裁切图片的X坐标
 * @param int $clip_y 被裁切图片的Y坐标
 * @param int $clip_width 被裁区域的宽度
 * @param int $clip_height 被裁区域的高度
 * image_clip('xxx/x.jpg', 'xxx/newx.jpg', 10, 40, 150, 150)
 */
function image_clip(string $src_file, string $dest_file, int $clip_x, int $clip_y, int $clip_width, int $clip_height): int
{
    $img_size = getimagesize($src_file);
    if (empty($img_size)) {
        return 0;
    } else {
        $img_width = $img_size[0];
        $img_height = $img_size[1];
        if ($img_width == 0 || $img_height == 0) {
            return 0;
        }
    }

    if (!function_exists('imagecreatefromjpeg')) {
        copy($src_file, $dest_file);
        return filesize($dest_file);
    }
    $img_color = null;
    switch ($img_size[2]) {
        case 1 :
            $img_color = imagecreatefromgif($src_file);
            break;
        case 2 :
            $img_color = imagecreatefromjpeg($src_file);
            break;
        case 3 :
            $img_color = imagecreatefrompng($src_file);
            break;
    }

    if (!$img_color) {
        return 0;
    }

    $img_dst = imagecreatetruecolor($clip_width, $clip_height);
    imagefill($img_dst, 0, 0, 0xFFFFFF);
    imagecopyresampled($img_dst, $img_color, 0, 0, $clip_x, $clip_y, $img_width, $img_height, $img_width, $img_height);

    $conf = _SERVER('conf');
    $tmp_path = $conf['tmp_path'] ?? ini_get('upload_tmp_dir') . '/';
    $tmp_path == '/' and $tmp_path = './tmp/';

    $tmp_file = $tmp_path . md5($dest_file) . '.tmp';
    imagejpeg($img_dst, $tmp_file, 100);
    $n = filesize($tmp_file);
    copy($tmp_file, $dest_file);
    is_file($tmp_file) && @unlink($tmp_file);

    return $n;
}


// 先裁切后缩略，因为确定了，width, height, 不需要返回宽高。
function image_clip_thumb($src_file, $dest_file, $forced_width = 80, $forced_height = 80): int
{
    // 获取原图片宽高
    $img_size = getimagesize($src_file);
    if (empty($img_size)) {
        return 0;
    } else {
        $src_width = $img_size[0];
        $src_height = $img_size[1];
        if ($src_width == 0 || $src_height == 0) {
            return 0;
        }
    }

    $src_scale = $src_width / $src_height;
    $des_scale = $forced_width / $forced_height;

    if ($src_width <= $forced_width && $src_height <= $forced_height) {
        $des_width = $src_width;
        $des_height = $src_height;
        $n = image_clip($src_file, $dest_file, 0, 0, $des_width, $des_height);
        return filesize($dest_file);
        // 原图为横着的矩形
    } elseif ($src_scale >= $des_scale) {
        // 以原图的高度作为标准，进行缩略
        $des_height = $src_height;
        $des_width = $src_height / $des_scale;
        $n = image_clip($src_file, $dest_file, 0, 0, $des_width, $des_height);
        if ($n <= 0) return 0;
        $r = image_thumb($dest_file, $dest_file, $forced_width, $forced_height);
        return $r['filesize'];
        // 原图为竖着的矩形
    } else {
        // 以原图的宽度作为标准，进行缩略
        $des_width = $src_width;
        $des_height = $src_width / $des_scale;

        // echo "src_scale: $src_scale, src_width: $src_width, src_height: $src_height \n";
        // echo "des_scale: $des_scale, forcedwidth: $forcedwidth, forcedheight: $forcedheight \n";
        // echo "des_width: $des_width, des_height: $des_height \n";
        // exit;

        $n = image_clip($src_file, $dest_file, 0, 0, $des_width, $des_height);
        if ($n <= 0) return 0;
        $r = image_thumb($dest_file, $dest_file, $forced_width, $forced_height);
        return $r['filesize'];
    }
}


function image_safe_thumb($src_file, $id, $ext, $dir1, $forced_width, $forced_height, $random_name = 0): array
{
    $time = $_SERVER['time'];
    $ip = $_SERVER['ip'];
    $dir2 = image_set_dir($id, $dir1);
    $filename = $random_name ? md5(rand(0, 1000000000) . $time . $ip) . $ext : $id . $ext;
    $filepath = "$dir1$dir2/$filename";
    $arr = image_thumb($src_file, $filepath, $forced_width, $forced_height);
    $arr['file_url'] = "$dir2/$filename";
    return $arr;
}
