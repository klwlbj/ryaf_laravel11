<?php

namespace App\Http\Logic;

use App\Models\Order;
use App\Models\OrderAccountFlow;
use App\Models\Place;
use App\Models\SmokeDetector;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderLogic extends BaseLogic
{
    public function getList($params)
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
            $orderIds = User::query()
                ->where(function (Builder $q) use($params){
                    $q->orWhere('user_name','like',"%{$params['user_keyword']}%")
                        ->orWhere('user_phone','like',"%{$params['user_keyword']}%");
                })->select(['user_id'])->pluck('user_id')->toArray();

            $query->whereIn('order_id',$orderIds);
        }

        if(isset($params['start_date']) && !empty($params['start_date'])){
            $query->where('order_crt_time','>=',$params['start_date']);
        }

        if(isset($params['end_date']) && !empty($params['end_date'])){
            $query->where('order_crt_time','<=',$params['end_date']);
        }

        $total = $query->count();

        $list = $query
            ->select([
                'order.*',
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


        $accountFlowCountArr = OrderAccountFlow::query()->whereIn('orac_order_id',$ids)
            ->where(['orac_status' => 1])
            ->select([
                'orac_order_id',
                DB::raw('count(orac_order_id) as count'),
            ])->groupBy(['orac_order_id'])->get()->pluck('count','orac_order_id')->toArray();

        foreach ($list as $key => &$value){
            $value['order_place'] = $placeList[$value['order_id']] ?? 0;

            $value['order_device_count'] = $deviceCountArr[$value['order_id']] ?? 0;

            $value['account_flow_count'] = $accountFlowCountArr[$value['order_id']] ?? 0;
        }

        unset($value);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    public function addAccountFlow($params)
    {
        $orderData = Order::query()->where(['order_id' => $params['order_id']])->first();

        if(!$orderData){
            ResponseLogic::setMsg('订单数据不存在');
            return false;
        }

        $insertData = [
            'orac_order_id' => $params['order_id'],
            'orac_date' => $params['date'],
            'orac_pay_way' => $params['pay_way'],
            'orac_funds_received' => $params['funds_received'],
            'orac_type' => (date('Y-m',strtotime($orderData->order_crt_time)) == date('Y-m',strtotime($params['date']))) ? 1 : 2,
            'orac_remark' => $params['remark'] ?? '',
            'orac_status' => 1,
            'orac_operator_id' => AuthLogic::$userId,
        ];

        OrderAccountFlow::query()->insert($insertData);

        return [];
    }

    public function getAccountFlow($params)
    {
        $orderData = Order::query()->where(['order_id' => $params['order_id']])->first();

        if(!$orderData){
            ResponseLogic::setMsg('订单记录不存在');
            return false;
        }

        $query = OrderAccountFlow::query();

        if(isset($params['order_id']) && !empty($params['order_id'])){
            $query->where(['orac_order_id' => $params['order_id']]);
        }

        $list = $query
            ->orderBy('orac_status','asc')
            ->orderBy('orac_approve_time','asc')
            ->get()->toArray();

        foreach ($list as $key => &$value){
            $value['approve_auth'] = (AuthLogic::orderAccountApproveAuth() && $value['orac_status'] == 1) ? true : false;
            $value['orac_pay_way_msg'] = OrderAccountFlow::payWayMsg($value['orac_pay_way']);
            $value['orac_type_msg'] = OrderAccountFlow::typeMsg($value['orac_type']);
        }

        unset($value);

        return ['list' => $list ,'order_info' => $orderData];
    }

    public function approveAccountFlow($params)
    {
        if(!AuthLogic::orderAccountApproveAuth()){
            ResponseLogic::setMsg('没有操作权限');
            return false;
        }

        $data = OrderAccountFlow::query()->where(['orac_id' => $params['id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        if($data->orac_status != 1){
            ResponseLogic::setMsg('记录不为待审批状态');
            return false;
        }


        $updateData = [
            'orac_status' => 2,
            'orac_approve_id' => AuthLogic::$userId,
            'orac_approve_time' => date('Y-m-d H:i:s')
        ];

        DB::beginTransaction();

        if(OrderAccountFlow::query()->where(['orac_id' => $params['id']])->update($updateData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('审批回款记录失败');
            return false;
        }

        if(Order::query()->where(['order_id' => $data->orac_order_id])->update(['order_funds_received' => DB::raw("order_funds_received+".$data->orac_funds_received)]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新订单实收款失败');
            return false;
        }

        DB::commit();

        return [];
    }
}
