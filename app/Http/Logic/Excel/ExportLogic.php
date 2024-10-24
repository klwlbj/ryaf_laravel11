<?php

namespace App\Http\Logic\Excel;

use App\Http\Logic\BaseLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportLogic extends BaseLogic
{
    protected static $columnNameArr = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

    /**获取列名
     * @param $columnIndex
     * @return string
     */
    public static function getColumnName($columnIndex){
        $count = count(self::$columnNameArr);
        $group = intval(($columnIndex - 1)/count(self::$columnNameArr));
        $index = ($columnIndex - 1) - ($count * $group);

        return (empty($group) ? '' : self::$columnNameArr[$group-1]).self::$columnNameArr[$index];
    }

    public function export($title,$list,$fileName,$config = [])
    {
        $row = 1;       // 行数，从第2行开始（第1行已经输出标题）

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($title as $key => $value){
            $sheet->setCellValue([$key+1,$row], $value);
        }

        $row++;

        foreach ($list as $key => $date){
            $index = 1;
            foreach ($date as $k => $cellData){
                $sheet->setCellValue([$index,$row], $cellData);
                $index++;
            }
            $row++;
        }

        $sheet = $this->setConfig($sheet,$config);

        $writer = new Xlsx($spreadsheet);
        // 禁止公式计算
        $writer->setPreCalculateFormulas(false);

        $excelName = $fileName. '.xlsx';

        if(!ToolsLogic::createDir(storage_path('app/public/excel'))){
            ResponseLogic::setMsg('创建文件夹失败');
            return false;
        }
        $excelPath =  storage_path('app/public/excel/' . $excelName);

        $writer->save($excelPath);

        return ['url' => Storage::url('excel/' .$excelName )];
    }

    /**设置excel配置
     * @param Worksheet $sheet
     * @param $config
     * @return Worksheet
     */
    public function setConfig(Worksheet $sheet, $config)
    {
        foreach ($config as $key => $item){
            switch ($key){
                case 'wrap_text':
                    foreach ($item as $k => $v){
                        $sheet->getStyle($k)->getAlignment()->setWrapText($v);
                    }
                    break;
                case 'font_size':
                    foreach ($item as $k => $v){
                        $sheet->getStyle($k)->getFont()->setSize($v);
                    }
                    break;
                case 'width':
                    foreach ($item as $k => $v){
                        $sheet->getColumnDimension($k)->setWidth($v);
                    }
                    break;
                case 'height':
                    foreach ($item as $k => $v){
                        $sheet->getRowDimension($k)->setRowHeight($v);
                    }
                    break;
                case 'bold':
                    foreach ($item as $k => $v){
                        $sheet->getStyle($k)->getFont()->setBold($v);
                    }
                    break;
                case 'merge':
                    foreach ($item as $k => $v){
                        $sheet->mergeCells($k);
                    }
                    break;
                case 'horizontal_center':
                    foreach ($item as $k => $v){
                        $sheet->getStyle($k)
                            ->getAlignment()
                            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
                            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }
                    break;
                case 'vertical_center' :
                    foreach ($item as $k => $v){
                        $sheet->getStyle($k)
                            ->getAlignment()
                            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    }
                    break;
                case 'color':
                    foreach ($item as $k => $v){
                        $sheet->getStyle($k)->getFont()->getColor()->setARGB($v);
                    }
                    break;
                case 'freeze_pane':
                    foreach ($item as $k => $v){
                        $sheet->freezePane($k);
                    }
                    break;
                case 'sum_func':
                    foreach ($item as $k => $v){
                        $sheet->setCellValue($k,$v);
                    }
                    break;
            }
        }

        return $sheet;
    }
}
