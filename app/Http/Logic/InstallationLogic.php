<?php

namespace App\Http\Logic;

use App\Http\Logic\Excel\ExportLogic;
use App\Models\Node;
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
            ->leftJoin('node','order.order_node_id','=','node.node_id')
            ->whereNotNull('order_actual_delivery_date');

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

        if(!empty($params['place'])){
            $orderIds = Place::query()
                ->where('plac_name','like',"%{$params['place']}%")
                ->select(['plac_order_id'])->pluck('plac_order_id')->toArray();
            $query->whereIn('order_id',$orderIds);
        }

        if(!empty($params['street'])){
            $childIds = Node::getNodeChild($params['street']);
//            print_r($childIds);die;
            $query->whereIn('order_node_id',$childIds);
        }

        if(!empty($params['area'])){
            $childIds = Node::getNodeChild($params['area']);
//            print_r($childIds);die;
            $query->whereIn('order_node_id',$childIds);
        }

        if(!empty($params['village'])){
            $query->where(['order_node_id' => $params['village']]);
        }

        $subQuery = clone $query;

        $subQuery
            ->leftJoin('smoke_detector','smoke_detector.smde_order_id','=','order.order_id')
            ->select([
                DB::raw("1 as id"),
                DB::raw("count(1) as count"),
                'order_account_receivable',
                'order_funds_received'
            ])->groupBy('order.order_id');

        $summary = Order::query()
            ->select([
                DB::raw("count(count) as count"),
                DB::raw("sum(order_account_receivable) as order_account_receivable"),
                DB::raw("sum(order_funds_received) as order_funds_received")
            ])
            ->fromSub($subQuery, 'sub')->groupBy(['id'])->first();

        if(empty($summary)){
            $summary = [
                'count' => 0,
                'order_account_receivable' => 0,
                'order_funds_received' => 0
            ];
        }else{
            $summary = $summary->toArray();
        }
//        print_r($summary);die;

        $total = $query->count();

        if(!empty($params['export'])){
            if(empty($params['start_date']) || empty($params['end_date'])){
                ResponseLogic::setMsg('导出必须有时间范围');
                return false;
            }

            ini_set('memory_limit','512M');
            $list = $query
                ->select([
                    'order.order_id',
//                    'order_iid',
                    'order_user_name',
                    'order_user_mobile',
                    'order_pay_way',
                    'order_remark',
//                    'order_status',
                    'order_pay_cycle',
                    'order_actual_delivery_date',
                    'order_account_receivable',
                    'order_funds_received',
                    DB::raw("(case when cast( order_pay_cycle AS SIGNED ) > 1
                             then (case when (TIMESTAMPDIFF( MONTH, order_actual_delivery_date, CURDATE() ) / cast( order_pay_cycle AS SIGNED ) * order_account_receivable ) > order_funds_received then 1 else 0 end) else (case when order_account_receivable > order_funds_received then 1 else 0 end) end) as is_debt"),
                    'node.node_name as order_node_name'
                ])
                ->orderBy('order_id','desc')
                ->get()->toArray();
        }else{
            $list = $query
                ->select([
                    'order.order_id',
//                    'order_iid',
                    'order_user_name',
                    'order_user_mobile',
                    'order_pay_way',
                    'order_remark',
                    'order_device_funds',
//                    'order_status',
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
        }

        $ids = array_column($list,'order_id');

//        $idsArr = array_chunk($ids,5000);
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

        if(!empty($params['export'])){
            return $this->export($list,$summary);
        }

        return [
            'total' => $total,
            'list' => $list,
            'summary' => $summary
        ];
    }

    public function export($list,$summary)

    {
        $title = ['安装日期','区域场所','单位','联系方式','详细地址','安装总数','备注（完成情况）','应收账款','是否付款','付款金额','未付金额','付款方案','收款路径','回款时间','是否要合同','是否开票','开票信息/备注'];

        $width = [];
        foreach ($title as $key => $value){
            if(in_array($value,['详细地址','回款时间'])){
                $width[ExportLogic::getColumnName($key+1)] = 50;
            }else{
                $width[ExportLogic::getColumnName($key+1)] = 20;
            }

        }

        $exportData = [];
        $config = [
            'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . '1' => true],
            'width' => $width
        ];

        $row = 2;
        foreach ($list as $key => $value){
            $address = array_column($value['order_place'],'plac_address');
            $flowArr = [];
            foreach ($value['account_flow_list'] as $item){
                $flowArr[] =  $item['orac_pay_way'] . ':' . $item['orac_funds_received'] . '￥'. ' ' . $item['orac_datetime'];
            }
            $exportData[] = [
                $value['order_actual_delivery_date'],
                $value['order_node_name'],
                $value['order_user_name'],
                $value['order_user_mobile'],
                implode("\n",$address),
                $value['order_device_count'],
                $value['order_remark'],
                $value['order_account_receivable'] ?: 0,
                ($value['order_funds_received'] > 0) ? '是' : '否',
                $value['order_funds_received'] ?: 0,
                ($value['order_account_receivable'] ?: 0) - ($value['order_funds_received'] ?: 0),
                ($value['order_pay_cycle'] > 1) ? ($value['order_pay_cycle'] . '期') : '一次性付款',
                $value['order_pay_way'],
                implode("\n",$flowArr),
                '',
                '',
                ''
            ];

            $row++;
        }

        $exportData[] = [
            '合计','','','','',$summary['count'],'',$summary['order_account_receivable'],'',$summary['order_funds_received'],( $summary['order_account_receivable'] - $summary['order_funds_received']),'','','','','',''
        ];

        $config['horizontal_center'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];
        $config['wrap_text'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];

        return ExportLogic::getInstance()->export($title,$exportData,'安装交付表',$config);
    }
}
