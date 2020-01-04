<?php
/**
 * Created by PhpStorm.
 * User: abellee
 * Date: 2019-04-09
 * Time: 04:18
 */

namespace App\BM\utils;


class Color
{
    public static function hex2rgba($color) {
        $r = 0;
        $g = 0;
        $b = 0;
        $a = 1;
        $default = [$r, $g, $b, $a];

        if(empty($color))
            return $default;

        if ($color[0] == '#' ) {
            $color = substr( $color, 1 );
        }

        if (strlen($color) == 6) {
            $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5], 'FF' );
        } elseif ( strlen( $color ) == 3 ) {
            $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2], 'FF' );
        } elseif ( strlen( $color ) == 8 ) {
            $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5], $color[6] . $color[7] );
        } else {
            return $default;
        }

        $rgba =  array_map('hexdec', $hex);
        return $rgba;
    }

}