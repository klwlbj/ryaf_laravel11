<?php

namespace App\Http\Logic;

use App\Models\Order;

class FinancialIncomeLogic extends BaseLogic
{
    public function getList($params)
    {
        $page     = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point    = ($page - 1) * $pageSize;

        $query = Order::query();
        $total = $query->count();
        $list  = Order::withCount('smokeDetectors')
            ->orderBy('order_id', 'desc')
            ->offset($point)->limit($pageSize)
            ->get()
            ->map(function ($item) {
                $item->advanced_total_installed  = '扫码';
                $item->project_type              = '烟感';
                $item->number                    = $item->smoke_detectors_count;
                $item->order_account_outstanding = $item->order_account_receivable - $item->order_funds_received;
                return $item;
            });

        return [
            'total' => $total,
            'list'  => $list,
        ];
    }

    public function getStageInfo($id)
    {
        $data = Order::query()
            ->where(['order_id' => $id])
            ->first();

        $actualDeliveryDate = $data->order_actual_delivery_date; // 交付日期

        $totalReceivable      = $data->order_account_receivable;  // 总应收款
        $totalReceived        = $data->order_funds_received;  // 总实收款
        $payCycle             = $data->order_pay_cycle ?? 1;  // 分期数
        $amountPerInstallment = bcdiv($totalReceivable, $payCycle, 2);  // 每期应收款

        $paymentDate = $actualDeliveryDate;
        for ($i = 0; $i < $payCycle; $i++) {
            // 添加一个月的时间间隔
            $paymentDate = date('Y-m-d', strtotime("+1 month", strtotime($paymentDate)));

            $list[] = [
                'date'   => $paymentDate,
                'amount' => max(min($totalReceived, $amountPerInstallment), 0),
            ];

            $totalReceived = bcsub($totalReceived, $amountPerInstallment, 2);
        }

        return $list;
    }
}
