<?php

namespace App\Http\Logic;

use DateTime;
use App\Models\Node;
use App\Models\Order;
use App\Models\Place;
use App\Models\OtherOrder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Logic\Excel\PaymentDetailExcelGenerator;
use App\Http\Logic\Excel\FinancialIncomeExcelGenerator;

class FinancialIncomeLogic extends BaseLogic
{
    public function getList($params)
    {
        $page     = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $offset   = ($page - 1) * $pageSize;
        // 开启查询日志
        DB::enableQueryLog();

        $model     = empty($params['order_project_type']) ? Order::class : OtherOrder::class;
        $query     = $model::query();
        $tableName = $query->getModel()->getTable();

        // 是否租赁
        if (isset($params['is_lease'])) {
            empty($params['order_project_type']) ? $query->where('order_contract_type', '以租代购') : $query->where('order_contract_type', OtherOrder::CONTRACT_TYPE_RENT);
        }

        if (!empty($params['order_project_type'])) {
            $query->where('order_project_type', $params['order_project_type']);
        }

        $nodeIds = '';
        $this->spliceNodeIds($params, $nodeIds);
        if (!empty($nodeIds)) {
            $query->where('order_node_ids', 'like', '%' . $nodeIds . ',%');
        }

        if (!empty($params['start_date'])) {
            $query->where('order_crt_time', '>=', $params['start_date']);
        }

        if (!empty($params['end_date'])) {
            $query->where('order_crt_time', '<=', $params['end_date']);
        }

        if (!empty($params['order_user_name'])) {
            $query->where('order_user_name', 'like', '%' . $params['order_user_name'] . '%');
        }
        if (isset($params['receivableFunds'])) {
            $query->whereColumn('order_account_receivable', '>', 'order_funds_received');
        }

        if (isset($params['arrears_duration']) && $params['arrears_duration'] !== '') {
            $condition = $params['arrears_duration'] == 7 ? '>=' : '=';

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
        // 获取最后一次查询的日志
        $queryLog = DB::getQueryLog();

        // 获取 WHERE 子句
        $whereClause   = end($queryLog)['query'];
        $bindingParams = (!empty($whereClause)) ? end($queryLog)['bindings'] : [];

        $whereClause = !empty($bindingParams) ? Str::after($whereClause, 'where') : '1=1';

        if (isset($params['receivableFunds'])) {
            $otherTotal = $this->totalStatistics($whereClause, $bindingParams);
        }

        $extraFields = ['overdue', 'returned_month', 'intra_day_remaining_funds'];
        if (isset($params['export']) && $params['export'] == 2) {
            $extraFields[] = 'arrears_month';
        }
        $select = $this->setSqlSelect($tableName, $extraFields);
        $list   = $query->selectRaw($select)
            ->when(empty($params['order_project_type']), function ($query) {
                return $query->withCount('smokeDetectors')
                    ->with([
                        'places'            => function ($query) {
                            $query->select('plac_order_id', 'plac_name');
                        },
                        'orderAccountFlows' => function ($query) {
                            $query->select('orac_order_id', 'orac_datetime');
                        },
                    ]);
            })
            ->when(!isset($params['export']), function ($query) use ($offset, $pageSize) {
                // 导出时不分页
                return $query->offset($offset)
                    ->orderBy('order_id', 'desc')
                    ->limit($pageSize)
                    ->get();
            });

        if (isset($params['export'])) {
            switch ($params['export']) {
                case '1':
                    // 导出-商务应收账款管理
                    $list           = Order::getCursorSortById($list);
                    $excelGenerator = new FinancialIncomeExcelGenerator();
                    return $excelGenerator->export($list, $params, $total, (array) $otherTotal);
                case '2':
                    // 导出-财务应收款项明细表
                    $list           = Order::getCursorSortById($list, 34550);
                    $excelGenerator = new PaymentDetailExcelGenerator();
                    return $excelGenerator->export($list, $params, $total);
                    break;
            }
        }

        foreach ($list as $item) {
            self::handleRow($item, $params);
        }

        return [
            'total'       => $total,
            'list'        => $list,
            'other_total' => $otherTotal,
        ];
    }

    public static function handleRow($item, $params = []): void
    {
        $model = $item->getModel();

        $item->is_overdue                = $item->overdue ? '是' : '否';
        $item->order_pay_cycle           = is_numeric($item->order_pay_cycle) ? $item->order_pay_cycle : 1;
        $item->income_type               = empty($params['order_project_type']) ? '对公转账' : $model::$formatPayWayMaps[$item->order_pay_way] ?? '无';
        $item->order_contract_type       = empty($params['order_project_type']) ? $item->order_contract_type : $model::$formatContractTypeMaps[$item->order_contract_type] ?? '无';
        $item->order_project_type        = empty($params['order_project_type']) ? '烟感' : $model::$formatProductTypeMaps[$item->order_project_type] ?? '无';
        $item->install_number            = empty($params['order_project_type']) ? $item->smoke_detectors_count : $item->order_delivery_number;
        $item->order_account_outstanding = $item->order_account_receivable - $item->order_funds_received;
        if (empty($params['order_project_type'])) {
            // 一次性获取所有区，街道，村委
            static  $districtNodes  = Node::whereIn('node_type', ['区县消防救援大队'])->get()->keyBy('node_id');
            static $districtNodeIds = $districtNodes->keys();
            static $streetNodes     = Node::whereIn('node_type', ['街道办'])->get()->keyBy('node_id');
            static $streetNodeIds   = $streetNodes->keys();
            static $villageNodes    = Node::whereIn('node_type', ['村委'])->get()->keyBy('node_id');
            static $villageNodeIds  = $villageNodes->keys();

            // 处理区域及地址信息
            $nodes = collect(explode(',', $item->order_node_ids));

            // 求两个集合的交集
            $districtIntersection = $districtNodeIds->intersect($nodes);
            $streetIntersection   = $streetNodeIds->intersect($nodes);
            $villageIntersection  = $villageNodeIds->intersect($nodes);
            if (!$districtIntersection->isEmpty()) {
                $item->district_name = $districtNodes[$districtIntersection->first()]['node_name'];
            }
            if (!$streetIntersection->isEmpty()) {
                $item->street_name = $streetNodes[$streetIntersection->first()]['node_name'];
            }
            if (!$villageIntersection->isEmpty()) {
                $item->village_name = $villageNodes[$villageIntersection->first()]['node_name'];
            }

            $item->address = $item->places->pluck('plac_name');
        }
        if (isset($params['receivableFunds'])) {
            $item->is_pay            = $item->order_funds_received ? '是' : '否';
            $item->returning_month   = $item->order_pay_cycle - $item->returned_month;
            $item->return_funds_time = $item->orderAccountFlows->pluck('orac_datetime');
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
                }
            }
        }
    }

    /**
     * 合计统计
     * @param $whereClause
     * @param $bindingParams
     * @return mixed
     */
    public function totalStatistics($whereClause, $bindingParams): mixed
    {
        return DB::selectOne("
                SELECT
                    sum( count_smde_id ) as install_number,
                    sum( order_funds_received ) AS order_funds_received,
                    sum( order_account_receivable )- sum( order_funds_received ) AS order_account_outstanding,
                    sum( order_amount_given ) AS order_amount_given,
                    sum( intra_day_remaining_funds ) AS intra_day_remaining_funds 
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
                            GREATEST(  round( LEAST(cast( order_pay_cycle AS SIGNED),TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE())) * order_account_receivable / cast( order_pay_cycle AS SIGNED ) - order_funds_received, 2 ), 0 ),
                            0 
                        ) AS intra_day_remaining_funds 
                    FROM
                        `order`
                        LEFT JOIN smoke_detector ON smoke_detector.smde_order_id = `order`.order_id
                        where {$whereClause}
                    GROUP BY
                        `order`.order_id 
                    ORDER BY
                    `order`.order_id DESC 
                    ) AS b ;

        ", $bindingParams);
    }

    /**
     * 获取分期信息
     * @param array $params
     * @return array
     */
    public function getStageInfo(array $params = []): array
    {
        $model = empty($params['order_project_type']) ? Order::class : OtherOrder::class;
        $data  = $model::query()
            ->where(['order_id' => $params['id']])
            ->first();

        $actualDeliveryDate   = $data->order_actual_delivery_date; // 交付日期
        $totalReceivable      = $data->order_account_receivable;  // 总应收款
        $totalReceived        = $data->order_funds_received;  // 总实收款
        $payCycle             = empty($data->order_pay_cycle) ? 1 : $data->order_pay_cycle;  // 分期数
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

    /**
     * 获取欠款信息
     * @param array $params
     * @return array
     */
    public function getArrearsInfo(array $params): array
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
		) as arrears_month
            ')
            ->where(['order_id' => $params['id']])
            ->first();

        self::handleArrearsItem($data);
        return [$data];
    }

    public static function handleArrearsItem($item): void
    {
        $orderPayCycle           = empty($item->order_pay_cycle) ? 1 : $item->order_pay_cycle;// 分期数
        $arrearsMonth            = $item->arrears_month; // 差几个月没还
        $orderFundsReceived      = $item->order_funds_received; // 已还
        $orderAccountOutstanding = bcsub($item->order_account_receivable, $item->order_funds_received, 2); //欠款
        $stageAmount             = bcdiv($item->order_account_receivable, $orderPayCycle, 2); // 每期应还
        $remainder               = $stageAmount == 0 ? 0 : bcmod($orderFundsReceived, $stageAmount, 2);// 最后一期取余

        $monthStructs = [
            [12, INF],
            [5, 12],
            [4, 5],
            [3, 4],
            [2, 3],
            [1, 2],
            [0, 1],
        ];
        foreach ($monthStructs as $key => $monthStruct) {
            if ($orderAccountOutstanding <= 0) {
                $currentArrears = 0;
            } else {
                $monthStart     = $monthStruct[0];
                $monthEnd       = $monthStruct[1] === INF ? $arrearsMonth : $monthStruct[1];
                $currentArrears = 0;
                if ($arrearsMonth > $monthStart && $arrearsMonth <= $monthEnd) {
                    static $lastMonth = true;
                    $currentArrears   = min($lastMonth && $orderPayCycle > 1 ? $stageAmount * ($monthEnd - $monthStart - 1) + $remainder : $stageAmount * ($monthEnd - $monthStart), $orderAccountOutstanding);
                    $lastMonth        = false;
                }
                if ($arrearsMonth > $monthEnd) {
                    $currentArrears = $orderPayCycle > 1 ? min($stageAmount * ($monthEnd - $monthStart), $orderAccountOutstanding) : 0;
                }
                if ($arrearsMonth < $monthStart) {
                    $currentArrears = 0;
                }
            }
            $item->{'arrears_' . 7 - $key} = $currentArrears;

            $orderAccountOutstanding = bcsub($orderAccountOutstanding, $currentArrears, 2);
        }
    }

    /**
     * 拼接街道节点id
     * @param array $params
     * @param string $nodeIds
     * @param int $number
     * @return void
     */
    public function spliceNodeIds(array $params = [], string &$nodeIds = '', int $number = 0): void
    {
        if (!empty($params['street_id_' . $number])) {
            $nodeIds .= ',' . $params['street_id_' . $number];
            $this->spliceNodeIds($params, $nodeIds, $number + 1);
        }
    }

    public function setSqlSelect($tableName, $extraFields)
    {
        $select = "`{$tableName}`.*";

        foreach ($extraFields as $extraField) {
            if ($extraField === 'overdue') {
                $select .= ",
            (
	CASE
		WHEN cast( order_pay_cycle AS SIGNED ) > 1 THEN
			( TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE() ) / cast( order_pay_cycle AS SIGNED ) * order_account_receivable ) > order_funds_received 
			ELSE order_account_receivable > order_funds_received 
	END 
	) as overdue";
            }
            if ($extraField === 'returned_month') {
                $select .= ",
	FLOOR( order_funds_received / (order_account_receivable / cast( order_pay_cycle AS SIGNED ))) as returned_month ";
            }
            if ($extraField === 'intra_day_remaining_funds') {
                $select .= ",
	IF
	(
			order_pay_cycle > 1,
			GREATEST(  round( LEAST(cast( order_pay_cycle AS SIGNED),TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE())) * order_account_receivable / cast( order_pay_cycle AS SIGNED ) - order_funds_received, 2 ), 0 ),
			0 
		) AS intra_day_remaining_funds ";
            }
            if ($extraField === 'arrears_month') {
                $select .= ",IFNULL(cast( order_pay_cycle AS SIGNED ), 1) as order_pay_cycle,
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
		) as arrears_month";
            }
        }

        return $select;
    }
}
