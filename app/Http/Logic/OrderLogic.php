<?php

namespace App\Http\Logic;

use App\Models\Order;
use App\Models\Place;
use App\Models\SmokeDetector;
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

        foreach ($list as $key => &$value){
            if(isset($placeList[$value['order_id']])){
                $value['order_place'] = $placeList[$value['order_id']];
            }else{
                $value['order_place'] = [];
            }

            if(isset($deviceCountArr[$value['order_id']])){
                $value['order_device_count'] = $deviceCountArr[$value['order_id']];
            }else{
                $value['order_device_count'] = 0;
            }

        }

        unset($value);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }
}
