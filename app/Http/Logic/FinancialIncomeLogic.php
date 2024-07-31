<?php

namespace App\Http\Logic;

use App\Models\Order;
use App\Models\Place;
use App\Models\OtherOrder;

class FinancialIncomeLogic extends BaseLogic
{
    public function getList($params)
    {
        $page     = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point    = ($page - 1) * $pageSize;

        $model = empty($params['order_project_type']) ? Order::class : OtherOrder::class;
        $query = $model::query();

        // 是否租赁
        if(isset($params['is_lease'])){
            empty($params['order_project_type']) ? $query->where('order_contract_type', '以租代购') : $query->where('order_contract_type', OtherOrder::CONTRACT_TYPE_RENT);
        }

        if (!empty($params['order_project_type'])) {
            $query->where('order_project_type', $params['order_project_type']);
        }

        if (!empty($params['start_date'])) {
            $query->where('order_crt_time', '>=', $params['start_date']);
        }

        if (!empty($params['end_date'])) {
            $query->where('order_crt_time', '<=', $params['end_date']);
        }

        if (isset($params['arrears_duration']) && $params['arrears_duration'] !== '') {
            $condition = $params['arrears_duration'] == 6 ? '>=' : '=';

            $query->whereRaw("(
	CASE
			
			WHEN IFNULL(cast( order_pay_cycle AS SIGNED ), 1) > 1 THEN
		IF
			((
					order_account_receivable - order_funds_received 
					) > 0,
				TIMESTAMPDIFF(
					MONTH,
					DATE_ADD( order_actual_delivery_date, INTERVAL FLOOR( order_funds_received / (order_account_receivable / cast( order_pay_cycle AS SIGNED ))) MONTH ) ,
				CURDATE()),
				0 
			) ELSE
		IF
			(( order_account_receivable - order_funds_received ) > 0, TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE())+ 1, 0 ) 
		END 
		) {$condition} ?", [$params['arrears_duration']]);
        }

        if (!empty($params['address'])) {
            $orderIds = Place::where('plac_name', 'like', '%' . $params['address'] . '%')->distinct()->pluck('plac_order_id');
            $query->whereIn('order_id', $orderIds);
        }

        $total = $query->count();

        $list = $query->selectRaw(
            '*,
            (
	CASE
		WHEN cast( order_pay_cycle AS SIGNED ) > 1 THEN
			( TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE() ) / cast( order_pay_cycle AS SIGNED ) * order_account_receivable ) > order_funds_received 
			ELSE order_account_receivable > order_funds_received 
	END 
	) as is_overdue'
        )
            ->when(empty($params['order_project_type']), function ($query) {
                return $query->withCount('smokeDetectors');
            })
            // ->withCount('smokeDetectors')
            ->orderBy('order_id', 'desc')
            ->offset($point)->limit($pageSize)
            ->get()
            ->map(function ($item) use ($params, $model) {
                $item->is_overdue                = $item->is_overdue ? '是' : '否';
                $item->order_pay_cycle           = is_numeric($item->order_pay_cycle) ? $item->order_pay_cycle : 1;
                $item->income_type               = empty($params['order_project_type']) ? '对公转账' : $model::$formatPayWayMaps[$item->order_pay_way] ?? '无';
                $item->order_contract_type       = empty($params['order_project_type']) ? $item->order_contract_type : $model::$formatContractTypeMaps[$item->order_contract_type] ?? '无';
                $item->order_project_type              = empty($params['order_project_type']) ? '烟感' : $model::$formatProductTypeMaps[$item->order_project_type] ?? '无';;
                $item->number                    = empty($params['order_project_type']) ? $item->smoke_detectors_count : $item->order_delivery_number;
                $item->order_account_outstanding = $item->order_account_receivable - $item->order_funds_received;
                return $item;
            });

        return [
            'total' => $total,
            'list'  => $list,
        ];
    }

    public function getStageInfo($params)
    {
        $model = empty($params['order_project_type']) ? Order::class : OtherOrder::class;
        $data = $model::query()
            ->where(['order_id' => $params['id']])
            ->first();

        $actualDeliveryDate = $data->order_actual_delivery_date; // 交付日期

        $totalReceivable      = $data->order_account_receivable;  // 总应收款
        $totalReceived        = $data->order_funds_received;  // 总实收款
        $payCycle             = !isset($data->order_pay_cycle) || empty($data->order_pay_cycle) ? 1 : $data->order_pay_cycle;  // 分期数
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

    public function getArrearsInfo($params)
    {
        $model = empty($params['order_project_type']) ? Order::class : OtherOrder::class;
        $data = $model::query()
            ->selectRaw('*, IFNULL(cast( order_pay_cycle AS SIGNED ), 1) as order_pay_cycle,
            (
	CASE
			
			WHEN IFNULL(cast( order_pay_cycle AS SIGNED ), 1) > 1 THEN
		IF
			((
					order_account_receivable - order_funds_received 
					) > 0,
				TIMESTAMPDIFF(
					MONTH,
					DATE_ADD( order_actual_delivery_date, INTERVAL FLOOR( order_funds_received / (order_account_receivable / cast( order_pay_cycle AS SIGNED ))) MONTH ) ,
				CURDATE()),
				0 
			) ELSE
		IF
			(( order_account_receivable - order_funds_received ) > 0, TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE())+ 1, 0 ) 
		END 
		) as arrears_month,TIMESTAMPDIFF(MONTH, order_actual_delivery_date, CURDATE())  as pass_month
            ')
            ->where(['order_id' => $params['id']])
            ->first();

        $orderPayCycle           = empty($data->order_pay_cycle) ? 1 : $data->order_pay_cycle;
        $arrearsMonth            = $data->arrears_month; // 差几个月没还
        $orderFundsReceived      = $data->order_funds_received;
        $orderAccountOutstanding = bcsub($data->order_account_receivable, $data->order_funds_received, 2); //欠款
        $stageAmount             = bcdiv($data->order_account_receivable, $orderPayCycle, 2); // 每期应还
        $remainder               = bcmod($orderFundsReceived, $stageAmount, 2);// 最后一期取余

        $a = 1;
        for ($i = 1; $i < 7; $i++) {
            if ($i <= $arrearsMonth) {
                $data->{'arrears_' . $i} = ($arrearsMonth == $i) ? bcsub($stageAmount, $remainder, 2) : ($orderPayCycle > 1 ? $stageAmount : 0);
            } else {
                $data->{'arrears_' . $i} = 0;
            }
            if ($i === 6 && $arrearsMonth >= 6) {
                $data->{'arrears_' . $i} = $orderAccountOutstanding;
            }
            $orderAccountOutstanding = bcsub($orderAccountOutstanding, ($orderPayCycle > 1 ? $stageAmount : 0), 2);
        }

        return [$data];
    }
}
