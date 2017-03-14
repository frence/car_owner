<?php

//文字生成图片
//生成缩略图
//GD库支持
//返回图片，默认为png格式
//调用时使用header("Content-type: image/png");
class TextImage {

    public static
            $param = array('font' => './Public/Fonts/simhei.ttf', //默认字体. 相对于脚本存放目录的相对路径.
        'text' => 'undefined', // 默认文字.
        'size' => 14, //字体大小
        'rot' => 0, //旋转角度.
        'pad' => 0, //填充.
        'transparent' => 1, // 文字透明度.
        'red' => 66, //文字颜色
        'grn' => 51,
        'blu' => 113,
        'bg_red' => 255, //背景颜色
        'bg_grn' => 255,
        'bg_blu' => 255,
    );

    //初始化
    public function __construct($param = array()) {
        if (!empty($param)) {
            foreach (self::$param as $key => $value) {
                static::$param[$key] = !empty($param[$key]) ? $param[$key] : $value;
            }
        }
    }

    static public function draw() {
        $width = 0;
        $height = 0;
        $offset_x = 0;
        $offset_y = 0;
        $bounds = array();
        $image = "";
        //确定文字高度.
        $bounds = imagettfbbox(self::$param['size'], self::$param['rot'], self::$param['font'], "W");
        switch (true) {
            case self::$param['rot'] < 0:
                $font_height = abs($bounds[7] - $bounds[1]);
                break;
            case self::$param['rot'] > 0:
                $font_height = abs($bounds[1] - $bounds[7]);
                break;
            default:
                $font_height = abs($bounds[7] - $bounds[1]);
        }
        //确定边框高度.
        $bounds = imagettfbbox(self::$param['size'], self::$param['rot'], self::$param['font'], self::$param['text']);
        switch (true) {
            case self::$param['rot'] < 0:
                $width = abs($bounds[4] - $bounds[0]);
                $height = abs($bounds[3] - $bounds[7]);
                $offset_y = $font_height;
                $offset_x = 0;
                break;
            case self::$param['rot'] > 0:
                $width = abs($bounds[2] - $bounds[6]);
                $height = abs($bounds[1] - $bounds[5]);
                $offset_y = abs($bounds[7] - $bounds[5]) + $font_height;
                $offset_x = abs($bounds[0] - $bounds[6]);
                break;
            default:
                $width = abs($bounds[4] - $bounds[6]);
                $height = abs($bounds[7] - $bounds[1]);
                $offset_y = $font_height;
                ;
                $offset_x = 0;
        }
        $image = imagecreate($width + (self::$param['pad'] * 2) + 1, $height + (self::$param['pad'] * 2) + 1);
        $background = imagecolorallocate($image, self::$param['bg_red'], self::$param['bg_grn'], self::$param['bg_blu']);
        $foreground = imagecolorallocate($image, self::$param['red'], self::$param['grn'], self::$param['blu']);
        if (self::$param['transparent'])
            imagecolortransparent($image, $background);
        imageinterlace($image, false);
        //画图.
        imagettftext($image, self::$param['size'], self::$param['rot'], $offset_x + self::$param['pad'], $offset_y + self::$param['pad'], $foreground, self::$param['font'], self::$param['text']);
        // 输出为png格式.
        imagepng($image);
    }

}
