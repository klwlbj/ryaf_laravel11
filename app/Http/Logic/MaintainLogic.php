<?php

namespace App\Http\Logic;

use App\Http\Logic\Excel\ExportLogic;
use App\Models\Dic;
use App\Models\Node;
use App\Models\Order;
use App\Models\Place;
use App\Models\SmokeDetector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
                ->leftJoin('admin','admin.admin_id','=','order.order_deliverer_id')
                ->where(['order_status' => '交付完成']);

        if(!empty($params['node_id'])){
            $childIds = Node::getNodeChild($params['node_id']);
            $query->whereIn('place.plac_node_id',$childIds);
        }

        if(!empty($params['user_keyword'])){
            $query->where(function (Builder $q) use ($params){
                $q->orWhere('user.user_name','=',$params['user_keyword'])
                    ->orWhere('user.user_mobile','=',$params['user_keyword']);
            });
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
        $nodeStreetArr = Node::getNodeStreet();
        if(!empty($params['export'])){
            ini_set( 'max_execution_time', 72000 );
            ini_set( 'memory_limit', '2048M' );
            $list = $query->select([
                'plac_name',
                'place.plac_address',
                'node.node_id',
                'node.node_name',
                'user_name',
                'user_mobile',
                'smde_model_name',
                'smde_imei',
                'smde_last_heart_beat',
                'smde_extra_remark',
                'order_service_date',
                'smde_last_nb_module_battery',
                'smde_last_signal_intensity',
                'admin_name',
                'order_actual_delivery_date'
            ])->orderBy('order_actual_delivery_date','desc')->get()->toArray();


            $title = ['imei','型号','街道','监控中心','单位名称','安装地址','用户名称','用户联系方式','交付人员','交付时间','最近心跳包','最近电量','最近信号强度','服务期限','标注'];

            $exportData = [];
            $config = [
                'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(14) . '1' => true],
                'width' => ['A'=>20,'B'=>20,'C'=>20,'D'=>20,'E'=>20,'F'=>20,'G'=>20,'H'=>20,'I'=>20,'J'=>20,'K'=>20,'L'=>20,'M'=>20,'N'=>20,]
            ];

            $row = 2;
            foreach ($list as $key => $value){
                $exportData[] = [
                    $value['smde_imei'] . "\t",
                    $value['smde_model_name'],
                    $nodeStreetArr[$value['node_id']]['node_name'] ?? '',
                    $value['node_name'],
                    $value['plac_name'],
                    $value['plac_address'],
                    $value['user_name'],
                    $value['user_mobile'],
                    $value['admin_name'],
                    $value['order_actual_delivery_date'],
                    $value['smde_last_heart_beat'],
                    $value['smde_last_nb_module_battery'],
                    $value['smde_last_signal_intensity'],
                    $value['order_service_date'],
                    $value['smde_extra_remark'],
                ];

                $row++;
            }
            $config['horizontal_center'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(14) . $row => true];
            $config['wrap_text'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(14) . $row => true];

            return ExportLogic::getInstance()->export($title,$exportData,'运维烟感数据',$config);
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
        ])->groupBy(['place.plac_id'])->orderBy('place.plac_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        $placeIds = array_column($list,'plac_id');

        #获取烟感数据
        $deviceGroup = SmokeDetector::query()
            ->leftJoin('order','order.order_id','=','smoke_detector.smde_order_id')
            ->whereIn('smde_place_id',$placeIds)
            ->whereIn('smde_type',['烟感','温感'])
            ->where('smde_order_id','>',0)
            ->select([
                'smde_id',
                'smde_place_id',
                'smde_brand_name',
                'smde_model_name',
                'smde_model_tag',
                'smde_imei',
                'smde_online_real',
                'smde_last_heart_beat',
                'smde_extra_remark',
                'order_service_date',
                'smde_last_nb_module_battery',
                'smde_last_signal_intensity'
            ])
            ->get()->groupBy('smde_place_id')->toArray();

        foreach ($list as $key => &$value){
            $value['children_list'] = $deviceGroup[$value['plac_id']] ?? [];
            foreach ($value['children_list'] as &$v){
                $v['smde_extra_remark'] = explode(',',$v['smde_extra_remark']);
            }
            unset($v);
        }

        unset($value);

        return ['list' => $list,'total' => $total];
    }

    public function setRemark($params)
    {
        ToolsLogic::writeLog('设置标注','maintain',$params);
        $extraRemark = ToolsLogic::jsonDecode($params['extra_remark']);
        $extraRemarkArr = [];
        foreach ($extraRemark as $key => $value)
        if(!empty($value['value'])){
            $extraRemarkArr[] = $value['value'];
        }

        if(!empty($extraRemarkArr)){
            $update = [
                'smde_extra_remark' => implode(',',$extraRemarkArr),
            ];
        }else{
            $update = [
                'smde_extra_remark' => ''
            ];
        }

        if(SmokeDetector::query()->where(['smde_id' => $params['id']])->update($update) === false){
            ResponseLogic::setMsg('更新失败');
            return false;
        }
        return [];
    }

    public function getRemarkInfo($params)
    {
        $data = SmokeDetector::query()->where(['smde_id' => $params])->select(['smde_extra_remark'])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $data->toArray();

        if(!empty($data['smde_extra_remark'])){
            $data['smde_extra_remark'] = explode(',',$data['smde_extra_remark']);
            $extraRemark = [];
            foreach ($data['smde_extra_remark'] as $key => $value){
                $extraRemark[] = [
                    'value' => $value
                ];
            }
        }else{
            $extraRemark = [
                [
                    'value' => ''
                ]
            ];
        }

        return [
            'extra_remark' => $extraRemark
        ];
    }

    public function getPlaceInfo($params)
    {
        $data = Place::query()->where(['plac_id' => $params['id']])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return  false;
        }
        $data = $data->toArray();
        $data['plac_node_arr'] = Node::getNodeParent($data['plac_node_id']);
        $peopleFeatureArr = explode(',',$data['plac_people_feature']);
        $tagsArr = explode(',',$data['plac_tags']);

        $tagList = Dic::query()->whereIn('dic_tag',['常驻人群特征','特殊标签'])->get()->toArray();
        $peopleFeature = [];
        $tag = [];

        foreach ($tagList as $key => $value){
            if($value['dic_tag'] == '常驻人群特征'){
                $peopleFeature[] = [
                    'label' => $value['dic_val'],
                    'value' => in_array($value['dic_val'],$peopleFeatureArr) ? true : false,
                ];
            }

            if($value['dic_tag'] == '特殊标签'){
                $tag[] = [
                    'label' => $value['dic_val'],
                    'value' => in_array($value['dic_val'],$tagsArr) ? true : false,
                ];
            }
        }

        $data['people_feature'] = $peopleFeature;
        $data['tags'] = $tag;

        return $data;
    }

    public function updatePlace($params)
    {
        ToolsLogic::writeLog('updatePlace：','maintain',$params);
        $data = Place::query()->where(['plac_id' => $params['id']])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return  false;
        }
        $data = $data->toArray();

        $updateData = [
            'plac_name' => $params['name'],
            'plac_address' => $params['address'],
            'plac_lng' => $params['lng'],
            'plac_lat' => $params['lat'],
            'plac_node_id' => $params['node_id'],
            'plac_people_count' => $params['people_count'] ?? 0,
            'plac_type' => $params['type'] ?? '点击选择',
            'plac_type2' => $params['type2'] ?? '点击选择',
        ];

        $peopleFeatureArr = [];
        $tags = [];

        $params['people_feature'] = ToolsLogic::jsonDecode($params['people_feature']);
        $params['tags'] = ToolsLogic::jsonDecode($params['tags']);

        foreach ($params['people_feature'] as $key => $value){
            if($value['value']){
                $peopleFeatureArr[] = $value['label'];
            }
        }

        foreach ($params['tags'] as $key => $value){
            if($value['value']){
                $tags[] = $value['label'];
            }
        }

        $nodeIds = Node::getNodeParent($params['node_id'],0);
        $updateData['plac_node_ids'] = ',' . implode(',',$nodeIds) . ',';

        $updateData['plac_people_feature'] = !empty($peopleFeatureArr) ? implode(',',$peopleFeatureArr) : '';
        $updateData['plac_tags'] = !empty($tags) ? implode(',',$tags) : '';

//        print_r($updateData);die;

        DB::beginTransaction();
        if(Place::query()->where(['plac_id' => $params['id']])->update($updateData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新单位表失败');
            return false;
        }

        #更新单位下设备表
        if(SmokeDetector::query()->where(['smde_place_id' => $params['id']])->update(['smde_node_ids' => $updateData['plac_node_ids']]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新单位下设备失败');
            return false;
        }

        #更新订单地址数据
        $orderPlaces = Place::query()->where(['plac_order_id' => $data['plac_order_id']])
            ->select(['plac_id','plac_name','plac_address'])->orderBy('plac_id','asc')->get()->toArray();

        if(Order::query()->where(['order_id' => $data['plac_order_id']])->update(['order_places' => ToolsLogic::jsonEncode($orderPlaces)]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新订单单位信息失败');
            return false;
        }

        DB::commit();

        return [];
    }

    public function noDataList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = SmokeDetector::query()
            ->leftJoin('order','order.order_id','=','smoke_detector.smde_order_id')
            ->leftJoin('admin','admin.admin_id','=','order.order_deliverer_id')
            ->leftJoin('place','place.plac_id','=','smoke_detector.smde_place_id')
            ->leftJoin('node','node.node_id','=','place.plac_node_id')
            ->whereRaw("COALESCE(smde_last_heart_beat,'') = ''")
            ->where('smde_place_id','>',0)
            ->where('smde_order_id','>',0)
            ->where('order_status','=','交付完成')
        ;

        if(!empty($params['imei'])){
            $query->where('smde_imei','=',$params['imei']);
        }

        if(!empty($params['node_id'])){
            $childIds = Node::getNodeChild($params['node_id']);
            $query->whereIn('place.plac_node_id',$childIds);
        }

        if(!empty($params['start_date'])){
            $query->where('order_actual_delivery_date','>=',$params['start_date']);
        }

        if(!empty($params['end_date'])){
            $query->where('order_actual_delivery_date','<=',$params['end_date'] . " 23:59:59");
        }

        if(!empty($params['export'])){
            $list = $query->select([
                'smde_imei',
                'admin_name',
                'order_actual_delivery_date',
                'plac_address',
                'node_name',
                'order_user_name',
                'order_user_mobile'
            ])->orderBy('order_actual_delivery_date','desc')
                ->get()->toArray();

            $title = ['imei','监控中心','安装地址','用户名称','用户联系方式','交付人','交付时间'];

            $exportData = [];
            $config = [
                'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(7) . '1' => true],
                'width' => ['A'=>20,'B'=>20,'C'=>20,'D'=>20,'E'=>20,'F'=>20,'G'=>20,]
            ];
            $row = 2;
            foreach ($list as $key => $value){
                $exportData[] = [
                    $value['smde_imei'] . "\t",
                    $value['node_name'],
                    $value['plac_address'],
                    $value['order_user_name'],
                    $value['order_user_mobile'],
                    $value['admin_name'],
                    $value['order_actual_delivery_date'],
                ];

                $row++;
            }
            $config['horizontal_center'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(7) . $row => true];
            $config['wrap_text'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(7) . $row => true];

            return ExportLogic::getInstance()->export($title,$exportData,'无数据烟感',$config);
        }

        $total = $query->count();

        $list = $query->select([
            'smde_imei',
            'admin_name',
            'order_actual_delivery_date',
            'plac_address',
            'node_name',
            'order_user_name',
            'order_user_mobile'
        ])->orderBy('order_actual_delivery_date','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();


        return ['list' => $list,'total' => $total];
    }
}
