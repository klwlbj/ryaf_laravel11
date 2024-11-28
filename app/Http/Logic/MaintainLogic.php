<?php

namespace App\Http\Logic;

use App\Models\Node;
use App\Models\Place;
use App\Models\SmokeDetector;

class MaintainLogic extends BaseLogic
{
    public function placeList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = Place::query()
                ->leftJoin('node','node.node_id','=','place.plac_node_id')
                ->leftJoin('order','place.plac_order_id','=','order.order_id')
                ->leftJoin('smoke_detector','smoke_detector.smde_place_id','=','place.plac_id')
                ->leftJoin('user','user.user_id','=','place.plac_user_id')
                ->where(['order_status' => '交付完成']);

        if(!empty($params['node_id'])){
            $childIds = Node::getNodeChild($params['node_id']);
            $query->whereIn('place.plac_node_id',$childIds);
        }

        if(!empty($params['imei'])){
            $query->where('smoke_detector.smde_imei',$params['imei']);
        }

        if(isset($params['online']) && $params['online'] !== ''){
            $query->where('smoke_detector.smde_online_real',$params['online']);
        }

        if(isset($params['none_heart_day']) && $params['none_heart_day'] !== ''){
            $query->whereRaw('smde_last_heart_beat < (NOW() - INTERVAL ' . $params['none_heart_day'] . ' DAY)');
        }

        if(isset($params['expired_day']) && $params['expired_day'] !== ''){
            $query->whereRaw('order_service_date < (NOW() - INTERVAL ' . $params['expired_day'] . ' DAY)');
        }

        $total = (clone $query)->select(['place.plac_id'])->distinct('place.plac_id')->count();
//        print_r($total);die;

        $list = $query->select([
            'place.plac_name',
            'place.plac_id',
            'place.plac_address',
            'node.node_name',
            'user_name',
            'user_mobile'
        ])->groupBy(['place.plac_id'])->orderBy('place.plac_id','desc')->offset($point)->limit($pageSize)->get()->toArray();

        $placeIds = array_column($list,'plac_id');

        #获取烟感数据
        $deviceGroup = SmokeDetector::query()
            ->leftJoin('order','order.order_id','=','smoke_detector.smde_order_id')
            ->whereIn('smde_place_id',$placeIds)
            ->whereIn('smde_type',['烟感','温感'])
            ->where('smde_order_id','>',0)
            ->select([
                'smde_place_id',
                'smde_brand_name',
                'smde_model_name',
                'smde_model_tag',
                'smde_imei',
                'smde_online_real',
                'smde_last_heart_beat',
                'order_service_date'
            ])
            ->get()->groupBy('smde_place_id')->toArray();

        foreach ($list as $key => &$value){
            $value['children_list'] = $deviceGroup[$value['plac_id']] ?? [];
        }

        unset($value);

        return ['list' => $list,'total' => $total];
    }
}
