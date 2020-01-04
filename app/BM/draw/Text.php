<?php
/**
 * Created by PhpStorm.
 * User: abellee
 * Date: 2019-04-09
 * Time: 03:13
 */

namespace App\BM\draw;


class Text
{
    public $value;
    public $size;
    public $color;
    public $x;
    public $y;
    public $fontFamily;

    public function __construct(string $value, int $size, string $color, float $x, float $y, string $fontFamily = null)
    {
        $this->value = $value;
        $this->size = $size;
        $this->color = $color;
        $this->x = $x;
        $this->y = $y;
        $this->fontFamily = $fontFamily;
    }
}