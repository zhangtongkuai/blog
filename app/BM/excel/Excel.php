<?php
/**
 * Created by PhpStorm.
 * User: abellee
 * Date: 2019-04-09
 * Time: 03:56
 */

namespace App\BM\excel;


class Excel
{
    /**
     * 读取时会从第二行开始读，所以第一行最好为标题或随意输入的内容
     * @important 线上慎用
     * @param string $path excel文件路径
     * @return array
     * 返回的格式为：
     * [
     *      {
     *          sheet_name: xxxx,
     *          rows: [...]
     *      },
     *      {
     *          sheet_name: yyyy,
     *          rows: [...]
     *      }
     * ]
     * @throws \Asan\PHPExcel\Exception\ReaderException
     */
    public static function read(string $path) {
        $reader = \Asan\PHPExcel\Excel::load($path, 'utf8');
        $data = [];

        $sheets = $reader->sheets();
        foreach ($sheets as $index => $sheet){
            $sheetData = [];
            $sheetData['sheet_name'] = $sheet['name'];
            $sheetData['rows'] = [];

            $reader->setSheetIndex($index);
            $reader->ignoreEmptyRow(true);
            while($reader->valid()){
                $reader->next();
                $row = $reader->current();
                if(isset($row)){
                    $sheetData['rows'][] = $row;
                }
            }
            $data[] = $sheetData;
        }
        return $data;
    }
}