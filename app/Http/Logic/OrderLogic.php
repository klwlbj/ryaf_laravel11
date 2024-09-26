<?php

namespace App\Http\Logic;

use App\Models\File;
use App\Models\Node;
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

        if(!empty($params['node_id'])){
            $childIds = Node::getNodeChild($params['node_id']);
            $query->whereIn('order_node_id',$childIds);
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
            $query->where('order_crt_time','>=',$params['start_date']);
        }

        if(isset($params['end_date']) && !empty($params['end_date'])){
            $query->where('order_crt_time','<=',$params['end_date']);
        }

        if(isset($params['is_debt']) && !empty($params['is_debt'])){
            $query->whereRaw("CASE
			WHEN cast( order_pay_cycle AS SIGNED ) > 1 THEN
		( TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE() ) / cast( order_pay_cycle AS SIGNED ) * order_account_receivable ) > order_funds_received ELSE order_account_receivable > order_funds_received END");
        }

        $total = $query->count();

        $list = $query
            ->select([
                'order.*',
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

    public function getInfo($params)
    {
        $orderData = Order::query()->where(['order_id' => $params['order_id']])->first();

        if(!$orderData){
            ResponseLogic::setMsg('订单数据不存在');
            return false;
        }

        return $orderData->toArray();
    }

    public function update($params)
    {
        $orderData = Order::query()->where(['order_id' => $params['order_id']])->first();

        if(!$orderData){
            ResponseLogic::setMsg('订单数据不存在');
            return false;
        }

//        $orderData = $orderData->toArray();

        $update = [];

        if(!empty($params['order_account_receivable'])){
            $update['order_account_receivable'] = $params['order_account_receivable'];
        }

        if(!empty($params['order_device_funds'])){
            $update['order_device_funds'] = $params['order_device_funds'];
        }

        if(!empty($update)){
            if(Order::query()->where(['order_id' => $params['order_id']])->update($update) === false){
                ResponseLogic::setMsg('更新订单失败');
                return false;
            }
        }

        return [];
    }

    public function addAccountFlow($params)
    {
        $orderData = Order::query()->where(['order_id' => $params['order_id']])->first();

        if(!$orderData){
            ResponseLogic::setMsg('订单数据不存在');
            return false;
        }

        $fileList = ToolsLogic::jsonDecode($params['image_list'] ?? []);

        $insertData = [
            'orac_order_id' => $params['order_id'],
            'orac_datetime' => $params['datetime'],
            'orac_pay_way' => $params['pay_way'],
            'orac_funds_received' => $params['funds_received'],
            'orac_type' => (date('Y-m',strtotime($orderData->order_crt_time)) == date('Y-m',strtotime($params['datetime']))) ? 1 : 2,
            'orac_remark' => $params['remark'] ?? '',
            'orac_status' => 1,
            'orac_operator_id' => AuthLogic::$userId,
        ];

        $id = OrderAccountFlow::query()->insertGetId($insertData);

        $fileInsertData = [];
        if(!empty($fileList)){
            foreach ($fileList as $key => $value){
                $fileInsertData[] = [
                    'file_relation_id' => $id,
                    'file_type' => 'order_account_flow',
                    'file_name' => $value['name'],
                    'file_ext' => $value['ext'],
                    'file_path' => $value['url'],
                ];
            }
        }

        if(File::query()->insert($fileInsertData) === false){
            ResponseLogic::setMsg('插入附件失败');
            return false;
        }

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

        $ids = array_column($list, 'orac_id');

        $imageList = File::query()
            ->whereIn('file_relation_id', $ids)->where(['file_type' => 'order_account_flow'])
            ->select(['file_relation_id','file_name','file_path'])->get()->groupBy('file_relation_id')->toArray();

        foreach ($list as $key => &$value){
            $value['approve_auth'] = (AuthLogic::orderAccountApproveAuth() && $value['orac_status'] == 1) ? true : false;
            $value['orac_pay_way_msg'] = OrderAccountFlow::payWayMsg($value['orac_pay_way']);
            $value['orac_type_msg'] = OrderAccountFlow::typeMsg($value['orac_type']);
            $value['image_list'] = $imageList[$value['orac_id']] ?? '';
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
