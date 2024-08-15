<?php

namespace App\Http\Logic\Excel;

use App\Http\Logic\BaseLogic;
use App\Http\Logic\ToolsLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

abstract class ExcelGenerator extends BaseLogic
{
    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '1024M');
    }

    public string $exportTitle = '导出excel';

    /**
     * 导出限制
     * @var int
     */
    public int $exportLimit = 1000000000;

    public int $defaultWidth = 20;

    /**
     * 是否锁定第一行
     * @var bool
     */
    public bool $lockFirstRow = false;
    /**
     * 是否开启最后一行的合计
     * @var bool
     */
    public bool $openLastRowTotal = false;

    /**
     * 排序字段 todo
     * @var array
     */
    protected array $sortFields = [];

    protected static array $columnNameArr = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

    public const FIRST_ROW = 1;
    public const ALL_ROW   = 2;

    /**
     * 获取可导出字段
     *
     * @return array
     */
    public function getExportColumns(): array
    {
        return [];
    }

    /**获取列名
     * @param $columnIndex
     * @return string
     */
    public static function getColumnName($columnIndex)
    {
        $count = count(self::$columnNameArr);
        $group = intval(($columnIndex - 1) / count(self::$columnNameArr));
        $index = ($columnIndex - 1) - ($count * $group);

        return (empty($group) ? '' : self::$columnNameArr[$group - 1]) . self::$columnNameArr[$index];
    }

    public function export($list, array $params, int $count = 0 ,$lastRowTotal = [])
    {
        // 条数超限或为0时不导出
        if ($count == 0) {
            ResponseLogic::setMsg('没有可导出的记录');
            return false;
        }
        if ($count > $this->exportLimit) {
            ResponseLogic::setMsg('可导出的记录不得超过' . $this->exportLimit);
            return false;
        }
        if (empty($this->getExportColumns())) {
            ResponseLogic::setMsg('无导出字段');
            return false;
        }

        // 创建新的 Excel 对象
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet = $this->setHeader($this->getExportColumns(), $sheet);

        // 锁定首行
        (!$this->lockFirstRow) ?: $sheet->freezePane('A2');

        foreach ($list as $index => $item) {
            $this->setTableContent($this->getExportColumns(), $sheet, $item, $index, $params);
            unset($item);
        }
        if ($lastRowTotal) {
            // 找到最后一行
            $lastRow = $sheet->getHighestRow() + 1;
            $this->handleLastRow($sheet, $lastRow, $lastRowTotal);
        }

        // 导出 Excel 文件
        $writer = new Xlsx($spreadsheet);
        // 禁止公式计算
        $writer->setPreCalculateFormulas(false);

        $time = time();

        if (!ToolsLogic::createDir(storage_path('app/public/excel'))) {
            ResponseLogic::setMsg('创建文件夹失败');
            return false;
        }
        $excelPath = storage_path('app/public/excel/' . $this->exportTitle . $time . '.xlsx');
        $writer->save($excelPath);

        return ['url' => Storage::url('excel/' . $this->exportTitle . $time . '.xlsx')];
    }

        protected function setHeader($columns, $sheet)
        {
            foreach ($columns  as $key => $column) {
                $sheet->setCellValue([$key + 1, 1], $column['name']);
                $columnName = $this->getColumnName($key + 1);

                // 设置宽度
                $sheet->getColumnDimension($columnName)->setWidth($column['width'] ?? $this->defaultWidth);

                // 启用单元格的文本自动换行选项
                !isset($column['wrap_text']) ?: $sheet->getStyle($columnName)->getAlignment()->setWrapText(true);

                // 文字大小
                !isset($column['font_size']) ?: $sheet->getStyle($columnName)->getFont()->setSize($column['font_size']);

                // 文字首行或全行加粗
                !isset($column['bold']) ?: $sheet->getStyle($columnName . ($column['bold'] === self::FIRST_ROW ? '1' : ''))->getFont()->setBold($column['bold']);

                // 文字首行或全行居中
                !isset($column['horizontal_center']) ?: $sheet->getStyle($columnName . ($column['horizontal_center'] === self::FIRST_ROW ? '1' : ''))
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            return $sheet;
        }

        protected function setTableContent($columns, $sheet, $item, $index, $params = []): void
        {
            $this->handleRow($item, $params);
            foreach ($columns  as $key => $column) {
                $indexName = $column['index'];
                $cellValue = $item?->{$indexName};
                if ($cellValue  instanceof Collection) {
                    $cellValue = $cellValue->implode("\n");
                }
                $sheet->setCellValueExplicit([$key + 1, $index + 2], $cellValue, $column['type'] ?? DataType::TYPE_STRING);
            }
            // unset($item);
        }

    abstract protected function handleRow($item, $params = []);

    abstract protected function handleLastRow($sheet, int $lastRow);
}
