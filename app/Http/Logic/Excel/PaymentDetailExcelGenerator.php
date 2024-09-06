<?php

namespace App\Http\Logic\Excel;

use App\Models\OrderAccountFlow;
use Illuminate\Support\Facades\DB;
use App\Http\Logic\FinancialIncomeLogic;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PaymentDetailExcelGenerator extends ExcelGenerator
{
    public string $exportTitle = '应收款项明细表（商户类）';

    public bool $lockFirstRow = true;

    // 公式计算
    public bool $preCalculateFormulas = true;

    /**
     * 获取可导出字段
     * @return array
     */
    public function getExportColumns(): array
    {
        return [
            [
                "name"  => '序号',
                "index" => 'order_iid',
                "type"  => DataType::TYPE_STRING,
                "width" => 20,
            ],
            [
                "name"  => '账龄',
                "index" => 'aging',
                "width" => 20,
            ],
            [
                "name"  => '所属单位',
                "index" => 'order_user_name',
                "width" => 20,
            ],
            [
                "name"      => '客商全称',
                "index"     => 'address',
                "width"     => 50,
                "wrap_text" => true,
            ],
            [
                "name"  => '发生日期',
                "index" => 'order_actual_delivery_date',
                "width" => 20,
            ],
            [
                "name"  => '汇总已还',
                "index" => 'order_funds_received',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
            [
                "name"         => '年初余额',
                "extra_handle" => true,
                "index"        => 'current_year_arrears',
                "width"        => 20,
            ],
            [
                "name"         => '月初余额',
                "extra_handle" => true,
                "index"        => 'current_month_arrears',
                "width"        => 20,
            ],
            [
                "name"         => '本月新增欠费',
                "extra_handle" => true,
                "index"        => 'current_month_deduct_arrears',
                "width"        => 20,
            ],
            [
                "name"         => '本月收回欠款',
                "extra_handle" => true,
                "index"        => 'current_month_add_arrears',
                "width"        => 20,
            ],
            [
                "name"         => '本年累计收回欠款',
                "extra_handle" => true,
                "index"        => 'current_year_returned',
                "width"        => 20,
            ],
            [
                "name"         => '汇总',
                "extra_handle" => true,
                "index"        => 'order_account_outstanding',
                "width"        => 20,
            ],
            [
                "name"  => '其中：1个月内（30天内）',
                "index" => 'arrears_1',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
            [
                "name"  => '其中：1-2个月内（30-60天内）',
                "index" => 'arrears_2',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
            [
                "name"  => '其中：2-3个月内（60-90天内）',
                "index" => 'arrears_3',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
            [
                "name"  => '其中：3-4个月内（90-120天内）',
                "index" => 'arrears_4',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
            [
                "name"  => '其中：4-5个月内（120-150天内）',
                "index" => 'arrears_5',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
            [
                "name"  => '其中：5-12个月内（150天至本年内）',
                "index" => 'arrears_6',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
            [
                "name"  => '其中：12个月以上',
                "index" => 'arrears_7',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
        ];
    }

    protected function handleRow($item, $params = [])
    {
        // 账龄
        $arrearsMonth = $item->arrears_month;
        $item->aging  = match (true) {
            $arrearsMonth >= 12 && $arrearsMonth <= 24 => '1-2年内',
            $arrearsMonth < 12                         => '1年内',
            $arrearsMonth >= 24 && $arrearsMonth <= 36 => '2-3年内',
            $arrearsMonth >= 36                        => '3年以上',
            default                                    => '',
        };

        FinancialIncomeLogic::handleArrearsItem($item);

        // 单独取id，之后再查询
        self::$ids[] = $item->order_id;
    }

    protected function reQuery(array $ids, array $params)
    {
        $startDate  = $params['statistics_start_date'];
        $startYear  = date('Y-01-01 00:00:00', strtotime($startDate));
        $startMonth = date('Y-m-01 00:00:00', strtotime($startDate));
        $endDate    = $params['statistics_end_date'];
        // 本月新增欠费
        $currentMonthDeductOrderAccountFlows = OrderAccountFlow::whereIn('orac_order_id', $ids)
            ->select('orac_order_id', DB::raw('-SUM(orac_funds_received) as total_sum'))
            ->where('orac_funds_received', '<', 0)
            ->whereBetween('orac_datetime', [$startDate, $endDate])
            ->groupBy('orac_order_id')
            ->pluck('total_sum', 'orac_order_id');

        // 本月收回欠款
        $currentMonthAddOrderAccountFlows = OrderAccountFlow::whereIn('orac_order_id', $ids)
            ->select('orac_order_id', DB::raw('SUM(orac_funds_received) as total_sum'))
            ->where('orac_funds_received', '>', 0)
            ->whereBetween('orac_datetime', [$startDate, $endDate])
            ->groupBy('orac_order_id')
            ->pluck('total_sum', 'orac_order_id');

        // 年前已还流水
        $yearAgoAddOrderAccountFlows = OrderAccountFlow::whereIn('orac_order_id', $ids)
            ->select('orac_order_id', DB::raw('SUM(orac_funds_received) as total_sum'))
            ->where('orac_funds_received', '>', 0)
            ->where('orac_datetime', '<', $startYear)
            ->groupBy('orac_order_id')
            ->pluck('total_sum', 'orac_order_id');

        // 年内月前已还流水
        $monthAgoAddOrderAccountFlows = OrderAccountFlow::whereIn('orac_order_id', $ids)
            ->select('orac_order_id', DB::raw('SUM(orac_funds_received) as total_sum'))
            ->where('orac_funds_received', '>', 0)
            ->whereBetween('orac_datetime', [$startYear, $startMonth])
            ->groupBy('orac_order_id')
            ->pluck('total_sum', 'orac_order_id');

        return compact('currentMonthDeductOrderAccountFlows', 'currentMonthAddOrderAccountFlows', 'yearAgoAddOrderAccountFlows', 'monthAgoAddOrderAccountFlows');
    }

    protected function reprocessing(array $reQueryList, $sheet)
    {
        // 1.年前已还 sum(+)
        // 2.年内月前已还 sum(+)
        // 年初余额 欠款额-年前已还
        // 月初余额 欠款额-年前已还-年内月前已还

        // 3.本月新增欠费 sum(-)
        // 4.本月收回欠款 sum(+)
        // 本年累计收回欠款 sum(+)  已还-年前已还
        foreach (self::$ids as $index => $id) {
            $trueIndex = $index + 2;
            foreach ($this->getExportColumns() as $key => $column) {
                $indexName = $column['index'];
                switch ($indexName) {
                    // 年初余额
                    case "current_year_arrears":
                        $cellValue = $reQueryList['yearAgoAddOrderAccountFlows'][$id] ?? 0;
                        $sheet->setCellValueExplicit([$key + 1, $trueIndex], "=(F{$trueIndex} - {$cellValue})", DataType::TYPE_FORMULA);
                        break;
                        // 月初余额
                    case "current_month_arrears":
                        $cellValue = ($reQueryList['yearAgoAddOrderAccountFlows'][$id] ?? 0) + ($reQueryList['monthAgoAddOrderAccountFlows'][$id] ?? 0);
                        $sheet->setCellValueExplicit([$key + 1, $trueIndex], "=(F{$trueIndex} - {$cellValue})", DataType::TYPE_FORMULA);
                        break;
                        // 本月新增欠款
                    case "current_month_deduct_arrears":
                        $cellValue = $reQueryList['currentMonthDeductOrderAccountFlows'][$id] ?? 0;
                        $sheet->setCellValueExplicit([$key + 1, $trueIndex], $cellValue, DataType::TYPE_NUMERIC);
                        break;
                        // 本月收回欠款
                    case "current_month_add_arrears":
                        $cellValue = $reQueryList['currentMonthAddOrderAccountFlows'][$id] ?? 0;
                        $sheet->setCellValueExplicit([$key + 1, $trueIndex], $cellValue, DataType::TYPE_NUMERIC);
                        break;
                        // 本年累计收回欠款
                    case "current_year_returned":
                        $cellValue = $reQueryList['yearAgoAddOrderAccountFlows'][$id] ?? 0;
                        $sheet->setCellValueExplicit([$key + 1, $trueIndex], "=(L{$trueIndex} - {$cellValue})", DataType::TYPE_FORMULA);
                        break;
                    case "order_account_outstanding":
                        $sheet->setCellValueExplicit([$key + 1, $trueIndex], "=(M{$trueIndex} + N{$trueIndex} + O{$trueIndex} + P{$trueIndex} + Q{$trueIndex} + R{$trueIndex} + S{$trueIndex})", DataType::TYPE_FORMULA);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    protected function setExportTitle($params)
    {
        $time = date('Y年m月', strtotime($params['statistics_start_date']));
        return "如约安防" . $time . $this->exportTitle;
    }
}
