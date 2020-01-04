<?php
/**
 * Created by PhpStorm.
 * User: abellee
 * Date: 2019-04-08
 * Time: 23:40
 */

namespace App\BM\draw;

use App\BM\utils\File;
use App\BM\utils\Utils;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class Draw
{
    /**
     * @param array $texts BM/draw/Text数组
     * @param string $imagePath 图片本地地址
     * @param string|null $fontPath 字体文件地址
     * @param bool $autoSaveFile 是否自动保存图片文件，如果为true，则返回文件名
     * @return \Intervention\Image\Image|string
     */
    public static function textOnImage(array $texts, string $imagePath, string $fontPath = null, bool $autoSaveFile = true) {
        $image = ImageManagerStatic::make($imagePath);
        foreach ($texts as $text){
            $image->text($text->value, $text->x, $text->y, function($font) use ($text, $fontPath){
                if(isset($text->fontFamily)){
                    $font->file($text->fontFamily);
                }else if(isset($fontPath)){
                    $font->file($fontPath);
                }
                $font->size($text->size);
                $font->color($text->color);
                $font->align('center');
                $font->valign('top');
            });
        }

        if($autoSaveFile){
            $uuid = Utils::uuid();
            $fileName = $uuid . '.' . File::extension($imagePath);
            $path = storage_path('app/' . $fileName);
            $image->save($path);
            return $fileName;
        }
        return $image;
    }

    public static function delete(string $fileName) {
        Storage::delete($fileName);
    }

    public static function getPath(string $fileName) {
        return storage_path('app/' . $fileName);
    }
}