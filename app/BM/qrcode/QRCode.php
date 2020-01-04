<?php
/**
 * Created by PhpStorm.
 * User: abellee
 * Date: 2019-04-09
 * Time: 04:09
 */

namespace App\BM\qrcode;

use App\BM\utils\Utils;

class QRCode
{
    /**
     * @param string $text
     * @param int $size
     * @param int $margin
     * @param bool $autoSaveFile
     * @param string|null $logoPath
     * @param int $logoWidth
     * @param int $logoHeight
     * @param array $foregroundColor 前景色 通过 Color::hex2rgba() 获取
     * @param array $backgroundColor 背景色 通过 Color::hex2rgba() 获取
     * @return \Endroid\QrCode\QrCode|string
     * @throws \Endroid\QrCode\Exception\InvalidPathException
     */
    public static function gen(string $text, int $size, int $margin = 2, bool $autoSaveFile = true, string $logoPath = null, int $logoWidth = 0, int $logoHeight = 0, array $foregroundColor = [0, 0, 0], array $backgroundColor = [255, 255, 255]) {
        $qrCode = new \Endroid\QrCode\QrCode($text);
        $qrCode->setWriterByName('png');
        $qrCode->setEncoding('UTF-8');
        $qrCode->setSize($size);
        $qrCode->setMargin($margin);
        $qrCode->setRoundBlockSize(true);
        if(isset($logoPath)){
            $qrCode->setLogoPath($logoPath);
            $qrCode->setLogoSize($logoWidth, $logoHeight);
            $qrCode->setForegroundColor(['r' => $foregroundColor[0], 'g' => $foregroundColor[1], 'b' => $foregroundColor[2]]);
            $qrCode->setBackgroundColor(['r' => $backgroundColor[0], 'g' => $backgroundColor[1], 'b' => $backgroundColor[2]]);
        }

        if($autoSaveFile){
            $uuid = Utils::uuid();
            $fileName = $uuid . '.png';
            $path = storage_path('app/' . $fileName);
            $qrCode->writeFile($path);
            return $fileName;
        }

        return $qrCode;
    }

    public static function delete(string $fileName) {
        Storage::delete($fileName);
    }

    public static function getPath(string $fileName) {
        return storage_path('app/' . $fileName);
    }
}