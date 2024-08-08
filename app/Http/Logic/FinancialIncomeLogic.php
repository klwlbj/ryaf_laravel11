<?php

namespace App\Http\Logic;

use DateTime;
use App\Models\Node;
use App\Models\Order;
use App\Models\Place;
use App\Models\OtherOrder;
use Illuminate\Support\Facades\DB;

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
        if (isset($params['is_lease'])) {
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
        $otherTotal = [];
        $total      = $query->count();

        $list = $query->selectRaw(
            '*,
            (
	CASE
		WHEN cast( order_pay_cycle AS SIGNED ) > 1 THEN
			( TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE() ) / cast( order_pay_cycle AS SIGNED ) * order_account_receivable ) > order_funds_received 
			ELSE order_account_receivable > order_funds_received 
	END 
	) as overdue,
	FLOOR( order_funds_received / (order_account_receivable / cast( order_pay_cycle AS SIGNED ))) as returned_month
	'
        )
            ->when(empty($params['order_project_type']), function ($query) {
                return $query->withCount('smokeDetectors')->with('places');
            })
            ->orderBy('order_id', 'desc')
            ->offset($point)->limit($pageSize)
            ->get();
        $districtNodes = $streetNodes = $villageNodes = $districtNodeIds = $villageNodeIds = $streetNodeIds = [];
        if (isset($params['receivableFunds'])) {
            $nodeIds         = $list->pluck('order_node_ids')->toArray();
            $nodeIdsStr      = implode(',', $nodeIds);
            $nodeArr         = array_unique(explode(',', $nodeIdsStr));
            $districtNodes   = Node::whereIn('node_id', $nodeArr)->whereIn('node_type', ['区县消防救援大队'])->get()->keyBy('node_id');
            $districtNodeIds = $districtNodes->keys();
            $streetNodes     = Node::whereIn('node_id', $nodeArr)->whereIn('node_type', ['街道办'])->get()->keyBy('node_id');
            $streetNodeIds   = $streetNodes->keys();
            $villageNodes    = Node::whereIn('node_id', $nodeArr)->whereIn('node_type', ['村委'])->get()->keyBy('node_id');
            $villageNodeIds  = $villageNodes->keys();
        }

        $a = 1;
        $list->map(function ($item) use ($params, $model, $districtNodes , $streetNodes , $villageNodes , $districtNodeIds , $villageNodeIds , $streetNodeIds) {
            $item->is_overdue                = $item->overdue ? '是' : '否';
            $item->order_pay_cycle           = is_numeric($item->order_pay_cycle) ? $item->order_pay_cycle : 1;
            $item->income_type               = empty($params['order_project_type']) ? '对公转账' : $model::$formatPayWayMaps[$item->order_pay_way] ?? '无';
            $item->order_contract_type       = empty($params['order_project_type']) ? $item->order_contract_type : $model::$formatContractTypeMaps[$item->order_contract_type] ?? '无';
            $item->order_project_type        = empty($params['order_project_type']) ? '烟感' : $model::$formatProductTypeMaps[$item->order_project_type] ?? '无';
            $item->number                    = empty($params['order_project_type']) ? $item->smoke_detectors_count : $item->order_delivery_number;
            $item->order_account_outstanding = $item->order_account_receivable - $item->order_funds_received;
            if (isset($params['receivableFunds'])) {
                // 处理区域及地址信息
                $nodes = collect(explode(',', $item->order_node_ids));

                // 求两个集合的交集
                $districtIntersection = $districtNodeIds->intersect($nodes);
                $streetIntersection = $streetNodeIds->intersect($nodes);
                $villageIntersection = $villageNodeIds->intersect($nodes);
                if(!$districtIntersection->isEmpty()){
                    $item->district_name = $districtNodes[$districtIntersection->first()]['node_name'];
                }
                if(!$streetIntersection->isEmpty()){
                    $item->street_name = $streetNodes[$streetIntersection->first()]['node_name'];
                }
                if(!$villageIntersection->isEmpty()){
                    $item->village_name = $villageNodes[$villageIntersection->first()]['node_name'];
                }

                $item->address = $item->places->pluck('plac_name');

                $item->is_pay                    = $item->order_funds_received ? '是' : '否';
                $item->returning_month           = $item->order_pay_cycle - $item->returned_month;
                $item->return_funds_time         = $item->overdue;
                $item->intra_day_remaining_funds = 0;
                if ($item->returning_month > 0) {
                    if ($item->order_pay_cycle == 1) {
                        $item->next_return_time = date('Y-m-d', strtotime($item->order_actual_delivery_date));
                    } else {
                        // 当前日期
                        $startDate = new DateTime($item->order_actual_delivery_date);
                        // n个月后
                        $startDate->modify('+' . $item->returned_month + 1 . ' months');
                        // 获取计算后的日期
                        $item->next_return_time = $startDate->format('Y-m-d');

                        $startDate = new DateTime($item->order_actual_delivery_date);

                        // 当前时间
                        $currentDate = new DateTime('now');

                        // 计算时间间隔
                        $interval = $currentDate->diff($startDate);

                        // 获取月份间隔
                        $months = $interval->format('%m');

                        $item->months = $months;

                        $payCycle             = empty($item->order_pay_cycle) ? 1 : $item->order_pay_cycle;  // 分期数
                        $amountPerInstallment = bcdiv($item->order_account_receivable, $payCycle, 2);  // 每期应收款

                        $totalShouldReturn = bcmul($amountPerInstallment, $months, 2); // 当前期限应还款

                        $balance = bcsub($totalShouldReturn, $item->order_funds_received, 2); // 差额

                        $item->intra_day_remaining_funds = max($balance, 0);
                    }
                }
            }
            return $item;
        });
        if (isset($params['receivableFunds'])) {
            $otherTotal = DB::selectOne('
                SELECT
                    sum( count_smde_id ) as sum_smoke_detector,
                    sum( order_funds_received ) AS sum_order_funds_received,
                    sum( order_account_receivable )- sum( order_funds_received ) AS sum_balance_funds,
                    sum( order_amount_given ) AS sum_order_amount_given,
                    sum( intra_day_remaining_funds ) AS sum_intra_day_remaining_funds 
                FROM
                    (
                    SELECT
                        `order`.order_iid,
                        order_pay_cycle,
                        count( smoke_detector.smde_id ) AS count_smde_id,
                        `order`.order_account_receivable,
                        `order`.order_funds_received,
                        order_amount_given,
                        order_actual_delivery_date,
                    IF
                        (
                            order_pay_cycle > 1,
                            GREATEST( round( TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE()) * order_account_receivable / cast( order_pay_cycle AS SIGNED ) - order_funds_received, 2 ), 0 ),
                            0 
                        ) AS intra_day_remaining_funds 
                    FROM
                        `order`
                        LEFT JOIN smoke_detector ON smoke_detector.smde_order_id = `order`.order_id
                        
                    GROUP BY
                        `order`.order_id 
                    ORDER BY
                    `order`.order_id DESC 
                    ) AS b;
');
        }

        return [
            'total'       => $total,
            'list'        => $list,
            'other_total' => $otherTotal,
        ];
    }

    public function getStageInfo($params)
    {
        $model = empty($params['order_project_type']) ? Order::class : OtherOrder::class;
        $data  = $model::query()
            ->where(['order_id' => $params['id']])
            ->first();

        $actualDeliveryDate = $data->order_actual_delivery_date; // 交付日期

        $totalReceivable      = $data->order_account_receivable;  // 总应收款
        $totalReceived        = $data->order_funds_received;  // 总实收款
        $payCycle             = !isset($data->order_pay_cycle) || empty($data->order_pay_cycle) ? 1 : $data->order_pay_cycle;  // 分期数
        $amountPerInstallment = bcdiv($totalReceivable, $payCycle, 2);  // 每期应收款

        $paymentDate = $actualDeliveryDate;
        $list        = [];
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
        $data  = $model::query()
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
