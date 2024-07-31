<?php

namespace App\Http\Logic;

use App\Models\Order;
use App\Models\OrderAccountFlow;
use App\Models\Place;
use App\Models\SmokeDetector;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class InstallationLogic extends BaseLogic
{
    public function summary($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = Order::query()
            ->leftJoin('node','order.order_node_id','=','node.node_id');

        if(isset($params['keyword']) && !empty($params['keyword'])){
            $query->where('order_iid','like',"%{$params['keyword']}%");
        }

        if(isset($params['address']) && !empty($params['address'])){
            $orderIds = Place::query()
                ->where('plac_address','like',"%{$params['address']}%")
                ->select(['plac_order_id'])->pluck('plac_order_id')->toArray();
            $query->whereIn('order_id',$orderIds);
        }

        if(isset($params['user_keyword']) && !empty($params['user_keyword'])){
            $userIds = User::query()
                ->where(function (Builder $q) use($params){
                    $q->orWhere('user_name','like',"%{$params['user_keyword']}%")
                        ->orWhere('user_mobile','like',"%{$params['user_keyword']}%");
                })->select(['user_id'])->pluck('user_id')->toArray();

            $query->whereIn('order_user_id',$userIds);
        }

        if(isset($params['start_date']) && !empty($params['start_date'])){
            $query->where('order_actual_delivery_date','>=',$params['start_date']);
        }

        if(isset($params['end_date']) && !empty($params['end_date'])){
            $query->where('order_actual_delivery_date','<=',$params['end_date']);
        }


        $total = $query->count();

        $list = $query
            ->select([
                'order.order_id',
                'order_iid',
                'order_user_name',
                'order_user_mobile',
                'order_remark',
                'order_status',
                'order_pay_cycle',
                'order_actual_delivery_date',
                'order_account_receivable',
                'order_funds_received',
                DB::raw("(case when cast( order_pay_cycle AS SIGNED ) > 1
                             then (case when (TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE() ) / cast( order_pay_cycle AS SIGNED ) * order_account_receivable ) > order_funds_received then 1 else 0 end) else (case when order_account_receivable > order_funds_received then 1 else 0 end) end) as is_debt"),
                'node.node_name as order_node_name'
            ])
            ->orderBy('order_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        $ids = array_column($list,'order_id');

        $placeList = Place::query()->whereIn('plac_order_id',$ids)->select([
            'plac_order_id',
            'plac_address',
        ])->get()->groupBy('plac_order_id')->toArray();

        $deviceCountArr = SmokeDetector::query()->whereIn('smde_order_id',$ids)
            ->select([
                'smde_order_id',
                DB::raw('count(smde_order_id) as count'),
            ])->groupBy(['smde_order_id'])->get()->pluck('count','smde_order_id')->toArray();


        $accountFlowGroup = OrderAccountFlow::query()->whereIn('orac_order_id',$ids)
            ->where(['orac_status' => 2])
            ->select([
                'orac_order_id',
                'orac_datetime',
                DB::raw("case when orac_pay_way=1 then '微信' when orac_pay_way=2 then '支付宝' when orac_pay_way=3 then '银行' when orac_pay_way=4 then '现金' else '二维码' end as orac_pay_way"),
                'orac_funds_received',
            ])->get()->groupBy(['orac_order_id'])->toArray();

        foreach ($list as $key => &$value){
            $value['order_place'] = $placeList[$value['order_id']] ?? [];

            $value['order_device_count'] = $deviceCountArr[$value['order_id']] ?? 0;

            $value['account_flow_list'] = $accountFlowGroup[$value['order_id']] ?? [];
        }

        unset($value);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }
}
