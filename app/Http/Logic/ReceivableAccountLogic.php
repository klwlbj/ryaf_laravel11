<?php

namespace App\Http\Logic;

use App\Models\Node;
use App\Models\Order;
use App\Models\Place;
use App\Models\ReceivableAccount;
use App\Models\ReceivableAccountAddress;
use App\Models\ReceivableAccountFlow;
use App\Models\SmokeDetector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReceivableAccountLogic extends BaseLogic
{
    public static $area = [
        '海珠',
        '大源',
        '番禺',
        '石井',
        '棠景',
        '松洲',
        '黄石',
        '石门',
        '太和',
        '荔湾',
        '越秀',
        '增城',
        '其他'
    ];

    public static $nodeRelationArr = [
        '海珠' => 61,
        '大源' => 7,
        '番禺' => 99,
        '石井' => 27,
        '棠景' => 40,
        '松洲' => 33,
        '黄石' => 38,
        '石门' => 26,
        '太和' => 24,
        '荔湾' => 153,
        '越秀' => 78,
        '增城' => 244,
        '其他' => 4,
    ];

    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = $this->getReceivableQuery($params);

//        print_r($query->toSql());die;

        $total = $query->count();

        #克隆出来用来做统计
        $statisticsQuery = clone $query;

        $statistics = $statisticsQuery->select([
            DB::raw("count(1) as count"),
            DB::raw("COALESCE(sum(reac_account_receivable),0) as account_receivable"),
            DB::raw("COALESCE(sum(reac_funds_received),0) as funds_received"),
        ])->first();


        $list = $query
            ->select([
                'reac_id',
                'node_name',
                'reac_installation_date',
                'reac_user_type',
                'reac_user_name',
                'reac_user_mobile',
                'reac_installation_count',
                'reac_given_count',
                'reac_account_receivable',
                'reac_funds_received',
                'reac_pay_cycle',
                'reac_remark'
            ])
            ->orderBy('reac_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        $ids = array_column($list,'reac_id');

        $addressList = ReceivableAccountAddress::query()->whereIn('reac_account_id',$ids)->select([
            'reac_account_id',
            'reac_address',
        ])->get()->groupBy('reac_account_id')->toArray();


        $accountFlowCountArr = ReceivableAccountFlow::query()->whereIn('reac_account_id',$ids)
            ->where(['reac_status' => 1])
            ->select([
                'reac_account_id',
                DB::raw('count(reac_account_id) as count'),
            ])->groupBy(['reac_account_id'])->get()->pluck('count','reac_account_id')->toArray();

        foreach ($list as $key => &$value){
            $value['address'] = $addressList[$value['reac_id']] ?? 0;

            $value['account_flow_count'] = $accountFlowCountArr[$value['reac_id']] ?? 0;
        }

        unset($value);
//        $statistics = [
//            'count' => 0,
//            'account_receivable' => 0,
//            'funds_received' => 0,
//        ];
        return [
            'total' => $total,
            'statistics' => $statistics,
            'list' => $list,
            'area' => self::$area
        ];
    }

    public function getReceivableQuery($params)
    {
        $query = ReceivableAccount::query()
                ->leftJoin('node','node.node_id','=','receivable_account.reac_node_id');

        if(isset($params['node_id']) && !empty($params['node_id'])){
            $nodeIds = Node::getNodeChild($params['node_id']);
            $query->whereIn('reac_node_id',$nodeIds);
        }

        if(isset($params['address']) && !empty($params['address'])){
            $ids = ReceivableAccountAddress::query()
                ->where('reac_address','like',"%{$params['address']}%")
                ->select(['reac_account_id'])->pluck('reac_account_id')->toArray();
            $query->whereIn('reac_id',$ids);
        }

        if(isset($params['user_keyword']) && !empty($params['user_keyword'])){
            $query->where(function (Builder $q) use($params){
                $q->orWhere('reac_user_name','like',"%{$params['user_keyword']}%")
                    ->orWhere('reac_user_mobile','like',"%{$params['user_keyword']}%");
            });
        }

        if(isset($params['start_date']) && !empty($params['start_date'])){
            $query->where('reac_installation_date','>=',$params['start_date']);
        }

        if(isset($params['end_date']) && !empty($params['end_date'])){
            $query->where('reac_installation_date','<=',$params['end_date']);
        }

        if(!empty($params['flow_start_date']) && !empty($params['flow_end_date'])){
            $ids = ReceivableAccountFlow::query()
                ->where('reac_datetime','>=',$params['flow_start_date'])
                ->where('reac_datetime','<=',$params['flow_start_date'] . ' 23:59:59')
                ->select(['reac_account_id'])->pluck('reac_account_id')->toArray();

            $query->whereIn('reac_id',$ids ?: [0]);
        }


        if(!empty($params['is_debt']) ){
//            $query->whereRaw("CASE
//			WHEN cast( reac_pay_cycle AS SIGNED ) > 1 THEN
//		( TIMESTAMPDIFF( MONTH, reac_installation_date, CURDATE() ) / cast( reac_pay_cycle AS SIGNED ) * reac_account_receivable ) > reac_funds_received ELSE reac_account_receivable    > reac_funds_received END");
            if($params['is_debt'] == 1){
                $query->where('reac_account_receivable' , '>',DB::raw("reac_funds_received"));
            }

            if($params['is_debt'] == 2){
                $query->where('reac_account_receivable' , '<=',DB::raw("reac_funds_received"));
            }

        }

        if(!empty($params['remark'])){
            if(!empty($params['remark_precise'])  && $params['remark_precise'] == 'true'){
                $query->where('reac_remark' , '=',$params['remark']);
            }else{
                $query->where('reac_remark' , 'like',"%" . $params['remark'] . "%");
            }
        }

        if(!empty($params['has_received']) && $params['has_received'] == 'true'){
            $query->where('reac_funds_received' , '>','0');
        }

        return $query;
    }

    public function getInfo($params)
    {
        $orderData = ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->first();

        if(!$orderData){
            ResponseLogic::setMsg('记录数据不存在');
            return false;
        }

        $orderData = $orderData->toArray();
        $orderData['order_node_arr'] = Node::getNodeParent($orderData['reac_node_id']);

        return $orderData;
    }

    public function update($params)
    {
        $data = ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->first();

        if(!$data){
            ResponseLogic::setMsg('收款数据不存在');
            return false;
        }

        $update = [];


        if(!empty($params['node_id'])){
            $update['reac_node_id'] = $params['node_id'];
        }

        if(!empty($params['user_name'])){
            $update['reac_user_name'] = $params['user_name'];
        }

        if(!empty($params['user_mobile'])){
            $update['reac_user_mobile'] = $params['user_mobile'];
        }

        if(!empty($params['installation_count'])){
            $update['reac_installation_count'] = $params['installation_count'];
        }

        if(!empty($params['given_count'])){
            $update['reac_given_count'] = $params['given_count'];
        }

        if(!empty($params['account_receivable'])){
            $update['reac_account_receivable'] = $params['account_receivable'];
        }


        if(!empty($update)){
            if(ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->update($update) === false){
                ResponseLogic::setMsg('更新记录失败');
                return false;
            }
        }

        return [];
    }

    public function delete($params)
    {
        $data = ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->first();

        if(!$data){
            ResponseLogic::setMsg('收款数据不存在');
            return false;
        }

        DB::beginTransaction();

        if(ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->delete() === false){
            DB::rollBack();
            ResponseLogic::setMsg('删除收款数据失败');
            return false;
        }

        #删除地址表
        if(ReceivableAccountAddress::query()->where(['reac_account_id' => $params['receivable_id']])->delete() === false){
            DB::rollBack();
            ResponseLogic::setMsg('删除收款地址数据失败');
            return false;
        }

        #删除流水表
        if(ReceivableAccountFlow::query()->where(['reac_account_id' => $params['receivable_id']])->delete() === false){
            DB::rollBack();
            ResponseLogic::setMsg('删除收款流水数据失败');
            return false;
        }

        DB::commit();
    }

    public function import($params)
    {
        ini_set( 'max_execution_time', 7200 );
        ini_set( 'memory_limit', '512M' );

//        $filename= $params['file']->getRealPath();
//
//        $inputFileType=  IOFactory::identify($filename);
//
//        $reader= IOFactory::createReader($inputFileType);

//        $spreadsheet=$reader->load($filename);


//        $spreadsheet = IOFactory::load($params['file']);
//
//        $sheetData = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
//
//        print_r($sheetData);die;
//        $sheetData = array_values($sheetData);
        $sheetData = ToolsLogic::jsonDecode($params['data']);

        $addressInsert = [];
        $flowInsert = [];
        $errorArr = [];

        $sucCount = 0;

        $existSn = [];

        $nodeArr = Node::query()->select(['node_id','node_name'])->pluck('node_id','node_name')->toArray();

        #获取存在数据
        $existArr = ReceivableAccount::query()
            ->leftJoin('receivable_account_address','receivable_account_address.reac_account_id','=','receivable_account.reac_id')
            ->select([
                DB::raw("CONCAT(receivable_account.reac_installation_date,'_',receivable_account.reac_user_mobile,'_',receivable_account.reac_installation_count,'_',receivable_account_address.reac_address) as str")
            ])->pluck('str')->toArray();

        foreach ($sheetData as $key => $value){
//            if($key == 0){
//                continue;
//            }
//            print_r($value);die;
            try {
                $value = array_values($value);
                $value[2] = ToolsLogic::convertExcelTime($value[2]);
                $value[15] = ToolsLogic::convertExcelTime($value[15]);
//            print_r($value);die;
                $area = $value[1];
                $orderSn = $value[0];
                $installationDate = date('Y-m-d',strtotime($value[2]));
                $userType = ($value[3] == '2C') ? 2 : 1;
                $street =  $value[4];
                $userName = $value[5];
                $userMobile = $value[6];
                $address = explode("\n",$value[7]);
                $installationCount = $value[8] ?: 0;
                $givenCount = is_numeric($value[9]) ? $value[8] : 0;
                $remark = $value[10] ?? '';
                $accountReceivable = $value[11] ?? 0;
                $fundsReceived = $value[12] ?? 0;
                $cycleType = $value[13];
                if(in_array($cycleType,['一次性付款','未确定'])){
                    $cycle = 1;
                }else{
                    $cycle = 36;
                }

                $receivedWay = $value[14] ?: '二维码';
                if($receivedWay == '对公'){
                    $payWay = 6;
                }else{
                    $payWay = 5;
                }

                $payData = $value[15];


                if(empty($userName)){
                    continue;
                }

                #如果应收少于等于0  直接跳过
                if($accountReceivable <= 0){
                    continue;
                }

                $nodeId = $nodeArr[$street] ?? '';

                if(empty($nodeId)){
                    $nodeId = self::$nodeRelationArr[$area];
                }


                if($installationDate == '1970-01-01'){
                    $errorArr[] = '行' . ($key + 2) . '记录安装时间异常  用户：' . $userName;
                    continue;
                }

                #如果存在订单编号 判断订单号唯一性
                if(!empty($orderSn)){
                    if(in_array($orderSn,$existSn)){
                        $errorArr[] = '行' . ($key + 2) . '的记录已存在  订单编号：' . $orderSn;
                        continue;
                    }

                    if(ReceivableAccount::query()->where(['reac_relation_sn' => $orderSn,'reac_type' => 1])->exists()){
                        $errorArr[] = '行' . ($key + 2) . '的记录已存在  订单编号：' . $orderSn;
                        continue;
                    }

                    $existSn[] = $orderSn;
                }else{
                    $existKey = $installationDate . '_' . $userMobile . '_' . $installationCount . '_' . ($address[0] ?? '');

                    #判断是否已存在记录
                    if(in_array($existKey, $existArr)){
                        $errorArr[] = '行' . ($key + 2) . '的记录已存在  用户：' . $userName . '(' . $userMobile . ')' . ' 安装日期：'.$installationDate . ' 安装台数：' . $installationCount . ' 安装地址：' . $address[0];
                        continue;
                    }
                    $existArr[] = $existKey;
                }

                #主数据
                $insert = [
                    'reac_type' => 1,
                    'reac_device_type' => 1,
                    'reac_relation_sn' => $orderSn ?: null,
                    'reac_node_id' => $nodeId,
                    'reac_installation_date' => $installationDate,
                    'reac_user_type' => $userType,
                    'reac_user_name' => $userName,
                    'reac_user_mobile' => $userMobile,
                    'reac_installation_count' => $installationCount,
                    'reac_given_count' => $givenCount,
                    'reac_account_receivable' => $accountReceivable,
                    'reac_funds_received' => $fundsReceived,
                    'reac_pay_cycle' => $cycle,
                    'reac_status' => 1,
                    'reac_remark' => $remark,
                    'reac_operator_id' => AuthLogic::$userId
                ];

                $id = ReceivableAccount::query()->insertGetId($insert);

                $sucCount ++ ;


//            $addressInsert = [];
                foreach ($address as $item){
                    $addressInsert[] = [
                        'reac_account_id' => $id,
                        'reac_address' => $item
                    ];
                }

                if(count($addressInsert) >= 500){
                    ReceivableAccountAddress::query()->insert($addressInsert);
                    $addressInsert = [];
                }

                #如果有实收 默认插入一条流水
                if($fundsReceived > 0){
                    $month = (!empty($payData) ? date('Ym',strtotime($payData)) : date('Ym'));
                    $flowInsert[] = [
                        'reac_account_id' => $id,
                        'reac_datetime' => $payData ?: date('Y-m-d H:i:s'),
                        'reac_pay_way' => $payWay,
                        'reac_funds_received' => $fundsReceived,
                        'reac_type' => (date('Ym',strtotime($installationDate)) ==  $month) ? 1 : 2,
                        'reac_status' => 2,
                        'reac_remark' => '自动生成第一条欠款',
                        'reac_operator_id' => AuthLogic::$userId,
                    ];

                    if(count($flowInsert) >= 500){
                        ReceivableAccountFlow::query()->insert($flowInsert);
                        $flowInsert = [];
                    }


                }
            } catch (\Exception $e) {
                $errorArr[] = '行' . ($key + 2) . '的记录异常：' . $e->getMessage();
                continue;
            }


        }

        if(!empty($addressInsert)){
            ReceivableAccountAddress::query()->insert($addressInsert);
            $addressInsert = [];
        }

        if(!empty($flowInsert)){
            ReceivableAccountFlow::query()->insert($flowInsert);
            $flowInsert = [];
        }

        $errorCount = count($errorArr);

        return ['success_count' => $sucCount,'error_count' => $errorCount,'error_arr' => $errorArr];
    }

    public function addFlow($params)
    {
        $data = ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        $data = $data->toArray();

        $insert = [
            'reac_account_id' => $params['receivable_id'],
            'reac_datetime' => $params['datetime'],
            'reac_pay_way' => $params['pay_way'],
            'reac_funds_received' => $params['funds_received'],
            'reac_type' => (date('Y-m',strtotime($data['reac_installation_date'])) == date('Y-m',strtotime($params['datetime']))) ? 1 : 2,
            'reac_remark' => $params['remark'] ?? '',
            'reac_status' => 2,
            'reac_operator_id' => AuthLogic::$userId
        ];


        DB::beginTransaction();

        $id = ReceivableAccountFlow::query()->insertGetId($insert);
        if($id === false){
            DB::rollBack();
            ResponseLogic::setMsg('插入流水数据失败');
            return false;
        }

        if(ReceivableAccount::query()->where(['reac_id' => $data['reac_id']])->update(['reac_funds_received' => DB::raw("reac_funds_received+".$params['funds_received'])]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新实收款失败');
            return false;
        }

        DB::commit();

        return [];
    }

    public function batchAddFlow($params)
    {
        $listQuery = ToolsLogic::jsonDecode($params['list_query']);
        $query = $this->getReceivableQuery($listQuery);

        $updateList = $query->select(['reac_id','reac_account_receivable','reac_funds_received','reac_installation_date'])->get()->toArray();

//        print_r($updateList);die;
        if($params['funds_type'] == 1){
            $fundsReceived = $params['funds_received'] ?? 0;
            if(empty($fundsReceived)){
                ResponseLogic::setMsg('收款金额不能为空');
                return false;
            }

            $ids = array_column($updateList,'reac_id');

            $flowInsertData = [];
            foreach ($updateList as $key => $value){
                $flowInsertData[] = [
                    'reac_account_id' => $value['reac_id'],
                    'reac_datetime' => $params['datetime'],
                    'reac_pay_way' => $params['pay_way'],
                    'reac_funds_received' => $fundsReceived,
                    'reac_type' => (date('Y-m',strtotime($value['reac_installation_date'])) == date('Y-m',strtotime($params['datetime']))) ? 1 : 2,
                    'reac_remark' => $params['remark'] ?? '',
                    'reac_status' => 2,
                    'reac_operator_id' => AuthLogic::$userId
                ];
            }

            DB::beginTransaction();

            if(ReceivableAccount::query()->whereIn('reac_id',$ids)->update(['reac_funds_received' => DB::raw("reac_funds_received+".$fundsReceived)]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('批量更新实收款失败');
                return false;
            }

            if(ReceivableAccountFlow::query()->insert($flowInsertData) === false){
                DB::rollBack();
                ResponseLogic::setMsg('批量插入流水记录失败');
                return false;
            }
            DB::commit();
        }else{
            #百分比
            $fundsPercent = $params['funds_percent'] ?? 0;
            if(empty($fundsPercent)){
                ResponseLogic::setMsg('百分比不能为空');
                return false;
            }

            $ids = array_column($updateList,'reac_id');

            $flowInsertData = [];
            foreach ($updateList as $key => $value){
                $fundsReceived = bcmul($value['reac_account_receivable'],$fundsPercent/100,2);
                if($fundsReceived == $value['reac_funds_received']){
                    continue;
                }
                $flowInsertData[] = [
                    'reac_account_id' => $value['reac_id'],
                    'reac_datetime' => $params['datetime'],
                    'reac_pay_way' => $params['pay_way'],
                    'reac_funds_received' => bcsub($fundsReceived,$value['reac_funds_received'],2),
                    'reac_type' => (date('Y-m',strtotime($value['reac_installation_date'])) == date('Y-m',strtotime($params['datetime']))) ? 1 : 2,
                    'reac_remark' => $params['remark'] ?? '',
                    'reac_status' => 2,
                    'reac_operator_id' => AuthLogic::$userId
                ];
            }
//            print_r($flowInsertData);die;
            DB::beginTransaction();

            if(ReceivableAccount::query()->whereIn('reac_id',$ids)->update(['reac_funds_received' => DB::raw("reac_account_receivable*".($fundsPercent/100))]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('批量更新实收款失败');
                return false;
            }

            if(ReceivableAccountFlow::query()->insert($flowInsertData) === false){
                DB::rollBack();
                ResponseLogic::setMsg('批量插入流水记录失败');
                return false;
            }
            DB::commit();

        }

        return [];
    }

    public function getFlow($params)
    {
        $data = ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        $data = $data->toArray();

        $list = ReceivableAccountFlow::query()
            ->where(['reac_account_id' => $params['receivable_id']])
            ->orderBy('reac_id','desc')
            ->get()->toArray();

        foreach ($list as $key => &$value){
            $value['reac_pay_way_msg'] = ReceivableAccountFlow::payWayMsg($value['reac_pay_way']);
            $value['reac_type_msg'] = ReceivableAccountFlow::typeMsg($value['reac_type']);
        }

        unset($value);

        return ['list' => $list ,'info' => $data];
    }

    public function syncOrder($params)
    {
        if(empty($params['start_date']) || empty($params['end_date'])){
            ResponseLogic::setMsg('请选择时间范围');
            return false;
        }

        ini_set( 'max_execution_time', 7200 );
        ini_set( 'memory_limit', '512M' );

        $params['end_date'] = $params['end_date'] . ' 23:59:59';

        $orderList = DB::connection('mysql2')->table('order')
//            ->leftJoin('receivable_account','receivable_account.reac_relation_id','=',DB::raw("order.order_id and receivable_account.reac_type = 2"))
            ->where('order.order_actual_delivery_date','>=',$params['start_date'])
            ->where('order.order_actual_delivery_date','<=',$params['end_date'])
//            ->where('order.order_account_receivable','>',0)
            ->select([
                'order.order_id',
                'order.order_iid',
                'order.order_node_id',
                'order.order_user_name',
                'order.order_user_mobile',
                'order.order_pay_cycle',
                'order.order_pay_way',
                'order_remark',
                'order_account_receivable',
                'order_funds_received',
                'order_crt_time',
                'order_actual_delivery_date'
            ])
            ->get()->toArray();

//        print_r($orderList);die;

        $orderIds = array_column($orderList,'order_id');

//        $nodeIds = array_values(array_unique(array_column($orderList,'order_node_id')));

        $placeGroup = DB::connection('mysql2')->table('place')->select([
            'plac_order_id',
            'plac_id',
            'plac_name',
            'plac_address'
        ])->whereIn('plac_order_id',$orderIds)->get()->groupBy('plac_order_id')->toArray();

//        $nodeArr = Node::query()->whereIn('node_id',$nodeIds)->select(['node_id','node_name'])->pluck('node_name','node_id')->toArray();

        $deviceCountArr = DB::connection('mysql2')->table('smoke_detector')->whereIn('smde_order_id',$orderIds)
            ->select([
                'smde_order_id',
                DB::raw('count(smde_order_id) as count'),
            ])->groupBy(['smde_order_id'])->get()->pluck('count','smde_order_id')->toArray();

//        print_r($placeGroup);die;
        $addressInsert = [];
        $flowInsert = [];

        $errorArr = [];
        foreach ($orderList as $key => $value){
            $value = (array)$value;

            //订单已存在
            if(ReceivableAccount::query()->where(['reac_type' => 2,'reac_relation_id' => $value['order_id']])->exists()){
                $errorArr[] = '订单记录已存在  订单编号：' . $value['order_iid'];
                continue;
            }

            $addressList = $placeGroup[$value['order_id']] ?? [];

//            print_r($addressList);die;

            $insertData = [
                'reac_type' => 2,
                'reac_device_type' => 1,
                'reac_relation_id' => $value['order_id'],
                'reac_relation_sn' => $value['order_iid'],
                'reac_node_id' => $value['order_node_id'],
                'reac_installation_date' => $value['order_actual_delivery_date'],
                'reac_user_type' => 2,
                'reac_user_name' => $value['order_user_name'],
                'reac_user_mobile' => $value['order_user_mobile'],
                'reac_installation_count' => $deviceCountArr[$value['order_id']] ?? 0,
                'reac_given_count' => 0,
                'reac_account_receivable' => $value['order_account_receivable'] ?: 0,
                'reac_funds_received' => $value['order_funds_received'] ?: 0,
                'reac_pay_cycle' => $value['order_pay_cycle'] ?: 1,
                'reac_status' => 1,
                'reac_remark' => $value['order_remark'],
                'reac_operator_id' => AuthLogic::$userId
            ];

            $id = ReceivableAccount::query()->insertGetId($insertData);

            foreach ($addressList as $addressItem){
                $addressItem = (array)$addressItem;
                $addressInsert[] = [
                    'reac_account_id' => $id,
                    'reac_place_id' => $addressItem['plac_id'],
                    'reac_address' => $addressItem['plac_address']
                ];
            }

            #如果实收大于0 则添加一条流水记录
            if($value['order_funds_received'] > 0){
                $flowInsert[] = [
                    'reac_account_id' => $id,
                    'reac_datetime' => date('Y-m-d H:i:s'),
                    'reac_pay_way' => 5,
                    'reac_funds_received' => $value['order_funds_received'],
                    'reac_type' => (date('Y-m',strtotime($value['order_actual_delivery_date'])) == date('Y-m',strtotime($params['datetime']))) ? 1 : 2,
                    'reac_status' => 2,
                    'reac_remark' => '自动生成第一条欠款',
                    'reac_operator_id' => AuthLogic::$userId,
                ];

                if(count($flowInsert) >= 500){
                    ReceivableAccountFlow::query()->insert($flowInsert);
                    $flowInsert = [];
                }
            }

            if(count($addressInsert) >= 500){
                ReceivableAccountAddress::query()->insert($addressInsert);
                $addressInsert = [];
            }
        }

        if(!empty($flowInsert)){
            ReceivableAccountFlow::query()->insert($flowInsert);
            $flowInsert = [];
        }

        if(!empty($addressInsert)){
            ReceivableAccountAddress::query()->insert($addressInsert);
            $addressInsert = [];
        }

        $errorCount = count($errorArr);
        $count = count($orderList);

        return ['success_count' => $count - $errorCount,'error_count' => $errorCount,'error_arr' => $errorArr];
    }
}
