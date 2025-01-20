<?php

namespace App\Http\Logic;

use App\Http\Logic\Excel\ExportLogic;
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
            DB::raw("COALESCE(sum(reac_installation_count),0) as reac_installation_count"),
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
                'reac_device_funds',
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

        if(isset($params['sn']) && !empty($params['sn'])){
            $query->where('reac_relation_sn','=',$params['sn']);
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
                ->where('reac_datetime','<=',$params['flow_end_date'] . ' 23:59:59')
                ->select(['reac_account_id'])->pluck('reac_account_id')->toArray();

            $query->whereIn('reac_id',$ids ?: [0]);
        }

        if(isset($params['flow_type']) && !empty($params['flow_type'])){
            $ids = ReceivableAccountFlow::query()
                ->where('reac_type','=',$params['flow_type'])
                ->select(['reac_account_id'])->pluck('reac_account_id')->toArray();
            $query->whereIn('reac_id',$ids);
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

        if(!empty($params['flow_remark'])){
            if(!empty($params['flow_remark_precise'])  && $params['flow_remark_precise'] == 'true'){
                $ids = ReceivableAccountFlow::query()
                    ->where('reac_remark' , '=',$params['flow_remark'])
                    ->select(['reac_account_id'])->pluck('reac_account_id')->toArray();

//                print_r($ids);die;
                $query->whereIn('reac_id',$ids);
            }else{
                $ids = ReceivableAccountFlow::query()
                    ->where('reac_remark' , 'like',"%" . $params['flow_remark'] . "%")
                    ->select(['reac_account_id'])->pluck('reac_account_id')->toArray();
                $query->whereIn('reac_id',$ids);
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
        $orderData['reac_singal_account_receivable'] = bcdiv($orderData['reac_account_receivable'],$orderData['reac_installation_count'] - $orderData['reac_given_count'],2);

        $orderData['reac_singal_device_funds'] = bcdiv($orderData['reac_device_funds'],$orderData['reac_installation_count'] - $orderData['reac_given_count'],2);

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

        if(isset($params['account_receivable']) && $params['account_receivable'] !== ''){
            if(!empty($params['installation_count'])){
                $update['reac_account_receivable'] = bcmul($params['installation_count'] - ($params['given_count'] ?? 0),$params['account_receivable'],2);
            }else{
                $update['reac_account_receivable'] = DB::raw("(reac_installation_count-reac_given_count)*".$params['account_receivable']);
            }
        }

        if(isset($params['device_funds']) && $params['device_funds'] !== ''){
            if(!empty($params['installation_count'])){
                $update['reac_device_funds'] = bcmul($params['installation_count'] - ($params['given_count'] ?? 0),$params['device_funds'],2);
            }else{
                $update['reac_device_funds'] = DB::raw("(reac_installation_count-reac_given_count)*".$params['device_funds']);
            }
        }


        if(!empty($params['remark'])){
            $update['reac_remark'] = $params['remark'];
        }
//        print_r($update);die;
        if(!empty($update)){
            if(ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->update($update) === false){
                ResponseLogic::setMsg('更新记录失败');
                return false;
            }
        }

        return [];
    }

    public function batchUpdate($params)
    {

        $listQuery = ToolsLogic::jsonDecode($params['list_query']);
        $query = $this->getReceivableQuery($listQuery);

        $update = [];


//        if(!empty($params['node_id'])){
//            $update['reac_node_id'] = $params['node_id'];
//        }
//
//        if(!empty($params['user_name'])){
//            $update['reac_user_name'] = $params['user_name'];
//        }
//
//        if(!empty($params['user_mobile'])){
//            $update['reac_user_mobile'] = $params['user_mobile'];
//        }

        if(!empty($params['installation_count'])){
            $update['reac_installation_count'] = $params['installation_count'];
        }

        if(!empty($params['given_count'])){
            $update['reac_given_count'] = $params['given_count'];
        }

        if(isset($params['account_receivable']) && $params['account_receivable'] !== ''){
            if(!empty($params['installation_count'])){
                $update['reac_account_receivable'] = bcmul($params['installation_count'] - ($params['given_count'] ?? 0),$params['account_receivable'],2);
            }else{
                $update['reac_account_receivable'] = DB::raw("(reac_installation_count-reac_given_count)*".$params['account_receivable']);
            }

        }

        if(isset($params['device_funds']) && $params['device_funds'] !== ''){
            if(!empty($params['installation_count'])){
                $update['reac_device_funds'] = bcmul($params['installation_count'] - ($params['given_count'] ?? 0),$params['device_funds'],2);
            }else{
                $update['reac_device_funds'] = DB::raw("(reac_installation_count-reac_given_count)*".$params['device_funds']);
            }
        }


        if(!empty($update)){
            if($query->update($update) === false){
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

        $errorData = [];

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
                    $payWay = 3;
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
                    $errorArr[] = '行' . ($key + 2) . '监控中心不存在  用户：' . $userName;

                    $value[] = '监控中心不存在';
                    $errorData[] = $value;
                    continue;
//                    $nodeId = self::$nodeRelationArr[$area];
                }


                if($installationDate == '1970-01-01'){
                    $errorArr[] = '行' . ($key + 2) . '记录安装时间异常  用户：' . $userName;

                    $value[] = '记录安装时间异常';
                    $errorData[] = $value;
                    continue;
                }

                #如果存在订单编号 判断订单号唯一性
                if(!empty($orderSn)){
                    if(in_array($orderSn,$existSn)){
                        $errorArr[] = '行' . ($key + 2) . '的记录已存在  订单编号：' . $orderSn;

                        $value[] = '记录已存在';
                        $errorData[] = $value;
                        continue;
                    }

                    if(ReceivableAccount::query()->where(['reac_relation_sn' => $orderSn,'reac_type' => 1])->exists()){
                        $errorArr[] = '行' . ($key + 2) . '的记录已存在  订单编号：' . $orderSn;

                        $value[] = '记录已存在';
                        $errorData[] = $value;
                        continue;
                    }

                    $existSn[] = $orderSn;
                }else{
                    $existKey = $installationDate . '_' . $userMobile . '_' . $installationCount . '_' . ($address[0] ?? '');

                    #判断是否已存在记录
                    if(in_array($existKey, $existArr)){
                        $errorArr[] = '行' . ($key + 2) . '的记录已存在  用户：' . $userName . '(' . $userMobile . ')' . ' 安装日期：'.$installationDate . ' 安装台数：' . $installationCount . ' 安装地址：' . $address[0];

                        $value[] = '记录已存在';
                        $errorData[] = $value;
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
                    'reac_device_funds' => 120*($installationCount-$givenCount),
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

        if(!empty($errorData)){
            $title = ['地区','订单编号','安装日期','客户类型','区域场所','单位','联系方式','地址','安装总数','赠送台数','备注（完成情况）','应收账款','是否付款','付款金额','未付金额','付款方案','收款路径','回款时间','错误信息'];

            $exportData = [];

            $width = [];
            foreach ($title as $key => $value){
                $width[chr(65 + $key)] = 30;
            }

            $config = [
                'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . '1' => true],
                'width' => $width
            ];
            $row = 2;
            foreach ($errorData as $key => $value){
                $exportData[] = [
                    $value[1],
                    $value[0],
                    $value[2],
                    $value[3],
                    $value[4],
                    $value[5],
                    $value[6],
                    $value[7],
                    $value[8],
                    $value[9],
                    $value[10],
                    $value[11],
                    '',
                    $value[12],
                    '',
                    $value[13],
                    $value[14],
                    $value[15],
                    $value[16],
                ];

                $row++;
            }
            $config['horizontal_center'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];
            $config['wrap_text'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];

            $exportReturn = ExportLogic::getInstance()->export($title,$exportData,'应收款导入失败数据',$config);
            $url = $exportReturn['url'];
        }else{
            $url = '';
        }

        $errorCount = count($errorArr);

        return ['success_count' => $sucCount,'error_count' => $errorCount,'error_arr' => $errorArr ,'error_url' => $url];
    }

    public function importReceipt($params)
    {
        ini_set( 'max_execution_time', 7200 );
        ini_set( 'memory_limit', '512M' );

        $sheetData = ToolsLogic::jsonDecode($params['data']);

        $addressInsert = [];
        $flowInsert = [];
        $errorArr = [];

        $sucCount = 0;

        $snArr = array_filter(array_column($sheetData,'0'), function($value) {
            // 返回true保留元素，返回false移除元素
            return (string)$value !== '';
        });

//        $nodeArr = Node::query()->select(['node_id','node_name'])->pluck('node_id','node_name')->toArray();

        $existList = ReceivableAccount::query()->where(['reac_type' => 2])
            ->whereIn('reac_relation_sn',$snArr)
            ->select(['reac_funds_received','reac_account_receivable','reac_id'])
            ->get()->keyBy('reac_relation_sn')->toArray();

        foreach ($sheetData as $key => $value) {
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
                $installationDate = date('Y-m-d', strtotime($value[2]));
                $userType = ($value[3] == '2C') ? 2 : 1;
                $street = $value[4];
                $userName = $value[5];
                $userMobile = $value[6];
                $address = explode("\n", $value[7]);
                $installationCount = $value[8] ?: 0;
                $givenCount = is_numeric($value[9]) ? $value[8] : 0;
                $remark = $value[10] ?? '';
                $accountReceivable = $value[11] ?? 0;
                $fundsReceived = $value[12] ?? 0;
                $cycleType = $value[13];
                if (in_array($cycleType, ['一次性付款', '未确定'])) {
                    $cycle = 1;
                } else {
                    $cycle = 36;
                }

                $receivedWay = $value[14] ?: '二维码';
                if ($receivedWay == '对公') {
                    $payWay = 3;
                } else {
                    $payWay = 5;
                }

                $payData = $value[15] ?: date('Y-m-d');


                if (empty($userName)) {
                    $errorArr[] = '行' . ($key + 2) . '用户名为空';

                    $value[] = '用户名为空';
                    $errorData[] = $value;
                    continue;
                }

                #如果应收少于等于0  直接跳过
                if ($accountReceivable <= 0) {
                    $errorArr[] = '行' . ($key + 2) . '应收少于等于0  用户：' . $userName;

                    $value[] = '应收少于等于0';
                    $errorData[] = $value;
                    continue;
                }


                if ($installationDate == '1970-01-01') {
                    $errorArr[] = '行' . ($key + 2) . '记录安装时间异常  用户：' . $userName;

                    $value[] = '记录安装时间异常';
                    $errorData[] = $value;
                    continue;
                }

                #如果存在订单编号
                if (!empty($orderSn)) {
                    if(!isset($existList[$orderSn])){
                        $errorArr[] = '行' . ($key + 2) . '订单编号数据不存在  用户：' . $userName;

                        $value[] = '订单编号数据不存在';
                        $errorData[] = $value;
                        continue;
                    }

                    $receivableData = $existList[$orderSn];
                } else {
                    $receivableList = ReceivableAccount::query()
                        ->leftJoin('receivable_account_address','receivable_account_address.reac_account_id','=','receivable_account.reac_id')
                        ->where([
                            'receivable_account.reac_installation_date' => $installationDate,
                            'receivable_account.reac_user_mobile' => $userMobile,
                            'receivable_account_address.reac_address' => $address[0] ?? '',
                        ])->select([
                            'receivable_account.reac_funds_received',
                            'receivable_account.reac_account_receivable',
                            'receivable_account.reac_id'
                        ])->get()->toArray();

                    if(empty($receivableList)){
                        $errorArr[] = '行' . ($key + 2) . '数据不存在  用户：' . $userName;
                        $value[] = '数据不存在';
                        $errorData[] = $value;
                        continue;
                    }

                    if(count($receivableList) > 1){
                        $errorArr[] = '行' . ($key + 2) . '存在多个数据  用户：' . $userName;
                        $value[] = '存在多个数据';
                        $errorData[] = $value;
                        continue;
                    }

                    $receivableData = $receivableList[0];
                }

                $updateData = [
                    'reac_installation_count' => $installationCount,
                    'reac_given_count' => $givenCount,
                    'reac_funds_received' => $fundsReceived,
                    'reac_remark' => $remark,
                ];

                ReceivableAccount::query()->where(['reac_id' => $receivableData['reac_id']])->update($updateData);

                #如果导入实收大于数据实收  则生成一条回款流水记录
                if($fundsReceived > $receivableData['reac_funds_received']){
                    $flowInsert[] = [
                        'reac_account_id' => $receivableData['reac_id'],
                        'reac_datetime' => $payData,
                        'reac_pay_way' => $payWay,
                        'reac_type' => (date('Ym',strtotime($installationDate)) ==  date('Ym',strtotime($payData))) ? 1 : 2,
                        'reac_status' => 2,
                        'reac_funds_received' => bcsub($fundsReceived,$receivableData['reac_funds_received'],2)
                    ];

                    if(count($flowInsert) >= 500){
                        ReceivableAccountFlow::query()->insert($flowInsert);
                        $flowInsert = [];
                    }
                }

                $sucCount++;
            } catch (\Exception $e) {
                $errorArr[] = '行' . ($key + 2) . '的记录异常：' . $e->getMessage();
                continue;
            }
        }

        if(!empty($flowInsert)){
            ReceivableAccountFlow::query()->insert($flowInsert);
            $flowInsert = [];
        }

        if(!empty($errorData)){
            $title = ['地区','订单编号','安装日期','客户类型','区域场所','单位','联系方式','地址','安装总数','赠送台数','备注（完成情况）','应收账款','是否付款','付款金额','未付金额','付款方案','收款路径','回款时间','错误信息'];

            $exportData = [];

            $width = [];
            foreach ($title as $key => $value){
                $width[chr(65 + $key)] = 30;
            }

            $config = [
                'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . '1' => true],
                'width' => $width
            ];

            $row = 2;
            foreach ($errorData as $key => $value){
                $exportData[] = [
                    $value[1],
                    $value[0],
                    $value[2],
                    $value[3],
                    $value[4],
                    $value[5],
                    $value[6],
                    $value[7],
                    $value[8],
                    $value[9],
                    $value[10],
                    $value[11],
                    '',
                    $value[12],
                    '',
                    $value[13],
                    $value[14],
                    $value[15],
                    $value[16],
                ];

                $row++;
            }
            $config['horizontal_center'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];
            $config['wrap_text'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];

            $exportReturn = ExportLogic::getInstance()->export($title,$exportData,'应收款导入失败数据',$config);
            $url = $exportReturn['url'];
        }else{
            $url = '';
        }

        $errorCount = count($errorArr);

        return ['success_count' => $sucCount,'error_count' => $errorCount,'error_arr' => $errorArr ,'error_url' => $url];
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

    public function deleteFlow($params)
    {
        ToolsLogic::writeLog('deleteFlow','receivable_account',$params);
        $data = ReceivableAccountFlow::query()->where(['reac_id' => $params['flow_id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        $data = $data->toArray();

        DB::beginTransaction();

        #回退实收
        if(ReceivableAccount::query()->where(['reac_id' => $data['reac_account_id']])->update(['reac_funds_received' => DB::raw("reac_funds_received-".$data['reac_funds_received'])]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('回退记录实收数据失败');
            return false;
        }

        #删除记录
        if(ReceivableAccountFlow::query()->where(['reac_id' => $params['flow_id']])->delete() === false){
            DB::rollBack();
            ResponseLogic::setMsg('删除流水数据失败');
            return false;
        }

        DB::commit();

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
//            ->where('order_status','=','交付完成')
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
                'order_device_funds',
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

        $existOrderIds = ReceivableAccount::query()
            ->whereIn('reac_relation_id',$orderIds)
            ->where(['reac_type' => 2])->select(['reac_relation_id'])->pluck('reac_relation_id')->toArray();

//        print_r($placeGroup);die;
        $addressInsert = [];
        $flowInsert = [];

        $errorArr = [];
        foreach ($orderList as $key => $value){
            $value = (array)$value;

            //订单已存在
            if(in_array($value['order_id'],$existOrderIds)){
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
                'reac_account_receivable' => ($value['order_account_receivable'] > 0) ? $value['order_account_receivable'] : ($deviceCountArr[$value['order_id']] ?? 0) * 240,
                'reac_device_funds' => ($value['order_device_funds'] > 0) ? $value['order_device_funds'] : ($deviceCountArr[$value['order_id']] ?? 0) * 120,
                'reac_funds_received' => $value['order_funds_received'] ?: 0,
                'reac_pay_cycle' => $value['order_pay_cycle'] ?: 1,
                'reac_status' => 1,
                'reac_remark' => $value['order_remark'],
                'reac_operator_id' => AuthLogic::$userId
            ];

            $id = ReceivableAccount::query()->insertGetId($insertData);
            $existOrderIds[] = $value['order_id'];
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

    public function exportFinance($params)
    {
        $query = $this->getReceivableQuery($params);

        $list = $query->select(['reac_id','reac_installation_date','reac_user_name','reac_user_mobile','reac_user_type','reac_node_id','reac_installation_count','reac_given_count','reac_account_receivable','reac_device_funds','reac_funds_received','reac_remark'])->get()->toArray();

        $receivableIds = array_column($list,'reac_id');

        $addressGroup = ReceivableAccountAddress::query()->whereIn('reac_account_id',$receivableIds)->select([
            'reac_account_id',
            'reac_address'
        ])->get()->groupBy('reac_account_id')->toArray();

        $flowGroup = ReceivableAccountFlow::query()->whereIn('reac_account_id',$receivableIds)->select([
            'reac_account_id',
            'reac_datetime',
            'reac_pay_way'
        ])->get()->groupBy('reac_account_id')->toArray();

        $nodeArr = Node::query()->select(['node_name','node_id'])->pluck('node_name','node_id')->toArray();

        $nodeStreetArr = Node::getNodeStreet();


        $title = ['序号','安装日期','单位名称/个人','联系方式','详细地址','街道办','收费日期','收入项目','收款项目','台数','赠送台数','应收金额(烟感设备120元/台)','应收金额(元)服务费','应收金额(元)','实收金额(元)','收款方式明细','备注','监控中心','客户类型'];

        $width = [];
        foreach ($title as $key => $value){
            if($value == '详细地址'){
                $width[chr(65 + $key)] = 40;
            }else{
                $width[chr(65 + $key)] = 20;
            }

        }

        $exportData = [];
        $config = [
            'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . '1' => true],
            'width' => $width,
            'freeze_pane' => ['A2' => true],
        ];

        $row = 2;

        foreach ($list as $key => $value){
            $addressList = $addressGroup[$value['reac_id']] ?? [];
            $flowList = $flowGroup[$value['reac_id']] ?? [];
            $payWayArr = [];
            $flowInfoArr = [];

            foreach ($flowList as $flowItem){
                $flowInfoArr[] = $flowItem['reac_datetime'] . " " . ReceivableAccountFlow::payWayMsg($flowItem['reac_pay_way']);
                $payWayArr[] = ReceivableAccountFlow::payWayMsg($flowItem['reac_pay_way']);
            }

//            $flowDateArr = array_column($flowList,'reac_datetime');
            $exportData[] = [
                $key + 1,
                $value['reac_installation_date'],
                $value['reac_user_name'],
                $value['reac_user_mobile'],
                implode("\n",array_column($addressList,'reac_address')),
                $nodeStreetArr[$value['reac_node_id']]['node_name'] ?? '',
                $value['reac_installation_date'],
                '烟感',
                $payWayArr[0] ?? '应收账款',
                $value['reac_installation_count'],
                $value['reac_given_count'],
                $value['reac_device_funds'],
                bcsub($value['reac_account_receivable'],$value['reac_device_funds'],2),
                $value['reac_account_receivable'],
                $value['reac_funds_received'],
                implode("\n",$flowInfoArr),
                $value['reac_remark'],
                $nodeArr[$value['reac_node_id']] ?? '',
                ($value['reac_user_type'] == 1) ? '2B' : '2C',
            ];

            $row++;
        }

        $config['horizontal_center'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];

        $config['wrap_text'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];

        return ExportLogic::getInstance()->export($title,$exportData,'财务解缴表',$config);
    }
}
