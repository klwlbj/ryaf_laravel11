<?php

namespace App\Http\Logic\Excel;

use App\Http\Logic\FinancialIncomeLogic;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class FinancialIncomeExcelGenerator extends ExcelGenerator
{
    public string $exportTitle = '应收账款管理导出excel';

    public bool $openLastRowTotal = true;

    public bool $lockFirstRow = true;

    /**
     * 获取可导出字段
     * @return array
     */
    public function getExportColumns(): array
    {
        return [
            [
                "name"  => 'Id',
                "index" => 'order_iid',
                "type"  => DataType::TYPE_STRING,
                "width" => 20,
            ],
            [
                "name"  => '所属区域',
                "index" => 'district_name',
                "width" => 30,
            ],
            [
                "name"  => '街道',
                "index" => 'street_name',
                "width" => 30,
            ],
            [
                "name"  => '村委/经济联社/社区',
                "index" => 'village_name',
                "width" => 30,
            ],
            [
                "name"      => '详细地址',
                "index"     => 'address',
                "width"     => 50,
                "wrap_text" => true,
            ],
            [
                "name"  => '单位/用户名称',
                "index" => 'order_user_name',
                "width" => 20,
            ],
            [
                "name"              => '联系方式',
                "index"             => 'order_user_mobile',
                "horizontal_center" => self::FIRST_ROW,
                "type"              => DataType::TYPE_STRING,
                "bold"              => self::FIRST_ROW,
                "width"             => 30,
            ],
            [
                "name"  => '客户类型',
                "index" => 'x',
                "width" => 20,
            ],
            [
                "name"  => '安装日期',
                "index" => 'order_actual_delivery_date',
                "width" => 30,
            ],
            [
                "name"  => '安装总数',
                "index" => 'install_number',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
            [
                "name"  => '赠送台数',
                "index" => 'order_amount_given',
                "type"  => DataType::TYPE_NUMERIC,
            ],
            [
                "name"  => '设备费',
                "index" => 'order_device_funds',
                "type"  => DataType::TYPE_NUMERIC,
            ],
            [
                "name"  => '合计应收款',
                "index" => 'order_account_receivable',
                "type"  => DataType::TYPE_NUMERIC,
            ],
            [
                "name"  => '是否付款',
                "index" => 'is_pay',
            ],
            [
                "name"  => '付款方案',
                "index" => 'order_contract_type',
            ],
            [
                "name"  => '已付金额（元）',
                "index" => 'order_funds_received',
                "type"  => DataType::TYPE_NUMERIC,
            ],
            [
                "name"  => '收款方式',
                "index" => 'income_type',
            ],
            [
                "name"      => '回款时间',
                "index"     => 'return_funds_time',
                "wrap_text" => true,
            ],
            [
                "name"  => '未付金额（元）',
                "index" => 'order_account_outstanding',
                "type"  => DataType::TYPE_NUMERIC,
            ],
            [
                "name"  => '分期数',
                "index" => 'order_pay_cycle',
                "type"  => DataType::TYPE_NUMERIC,
            ],
            [
                "name"  => '累计已付期数',
                "index" => 'returned_month',
                "type"  => DataType::TYPE_NUMERIC,
            ],
            [
                "name"  => '未付期数',
                "index" => 'returning_month',
                "type"  => DataType::TYPE_NUMERIC,
            ],
            [
                "name"  => '下一期应付款时间',
                "index" => 'next_return_time',
            ],
            [
                "name"  => '截止当天剩余应付款',
                "index" => 'intra_day_remaining_funds',
                "type"  => DataType::TYPE_NUMERIC,
            ],
        ];
    }

    protected function handleRow($item, $params = [])
    {
        FinancialIncomeLogic::handleRow($item, $params);
    }

    protected function handleLastRow($sheet, int $lastRow, $lastRowTotal = [])
    {
        $columns     = $this->getExportColumns();
        $columnNames = array_column($columns, 'index');
        foreach ($lastRowTotal as $key => $value) {
            $k = array_search($key, $columnNames);
            if ($k) {
                $sheet->setCellValueExplicit([$k + 1, $lastRow], $value, DataType::TYPE_NUMERIC);
            }
        }
    }
}
