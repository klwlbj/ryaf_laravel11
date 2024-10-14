<?php

namespace App\Http\Logic;

use App\Models\ReceivableAccount;
use App\Models\ReceivableAccountAddress;
use App\Models\ReceivableAccountFlow;
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
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = ReceivableAccount::query();


        if(isset($params['address']) && !empty($params['address'])){
            $ids = ReceivableAccountAddress::query()
                ->where('reac_address','like',"%{$params['address']}%")
                ->select(['reac_account_id'])->pluck('reac_account_id')->toArray();
            $query->whereIn('reac_id',$ids);
        }

        if(isset($params['user_keyword']) && !empty($params['user_keyword'])){
            $query->where(function (Builder $q) use($params){
                $q->orWhere('user_name','like',"%{$params['user_keyword']}%")
                    ->orWhere('user_mobile','like',"%{$params['user_keyword']}%");
            });
        }

        if(isset($params['start_date']) && !empty($params['start_date'])){
            $query->where('reac_installation_date','>=',$params['start_date']);
        }

        if(isset($params['end_date']) && !empty($params['end_date'])){
            $query->where('reac_installation_date','<=',$params['end_date']);
        }

        if(isset($params['is_debt']) && !empty($params['is_debt'])){
            $query->whereRaw("CASE
			WHEN cast( reac_pay_cycle AS SIGNED ) > 1 THEN
		( TIMESTAMPDIFF( MONTH, reac_installation_date, CURDATE() ) / cast( reac_pay_cycle AS SIGNED ) * reac_account_receivable ) > reac_funds_received ELSE reac_account_receivable    > reac_funds_received END");
        }

        $total = $query->count();

        $list = $query
            ->select([
                '*',
                DB::raw("(case when cast( reac_pay_cycle AS SIGNED ) > 1
                             then (case when (TIMESTAMPDIFF( MONTH, reac_installation_date, CURDATE() ) / cast( reac_pay_cycle AS SIGNED ) * reac_account_receivable ) > reac_funds_received then 1 else 0 end) else (case when reac_account_receivable > reac_funds_received then 1 else 0 end) end) as is_debt")
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

        return [
            'total' => $total,
            'list' => $list,
            'area' => self::$area
        ];
    }

    public function getInfo($params)
    {
        $orderData = ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->first();

        if(!$orderData){
            ResponseLogic::setMsg('记录数据不存在');
            return false;
        }

        return $orderData->toArray();
    }

    public function update($params)
    {
        $data = ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->first();

        if(!$data){
            ResponseLogic::setMsg('收款数据不存在');
            return false;
        }

        $update = [];

        if(!empty($params['area'])){
            $update['reac_area'] = $params['area'];
        }

        if(!empty($params['street'])){
            $update['reac_street'] = $params['street'];
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

    public function import($params)
    {
        ini_set( 'max_execution_time', 7200 );
        ini_set( 'memory_limit', '512M' );
        $spreadsheet = IOFactory::load($params['file']);

        $sheetData = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
        $sheetData = array_values($sheetData);
        $addressInsert = [];
        $flowInsert = [];
        $errorArr = [];

        $sucCount = 0;

        foreach ($sheetData as $key => $value){
            if($key == 0){
                continue;
            }

            $value = array_values($value);

            $area = $value[1];
            $installationDate = date('Y-m-d',strtotime($value[0]));
            $userType = ($value[2] == '2C') ? 2 : 1;
            $street =  $value[3];
            $userName = $value[4];
            $userMobile = $value[5];
            $address = explode("\n",$value[6]);
            $installationCount = $value[7] ?: 0;
            $givenCount = is_numeric($value[8]) ? $value[8] : 0;
            $remark = $value[9] ?? '';
            $accountReceivable = $value[10] ?? 0;
            $fundsReceived = $value[12] ?? 0;
            $cycleType = $value[14];
            if($cycleType == '一次性付款'){
                $cycle = 1;
            }else{
                $cycle = $value[15] ?: 36;
            }


            if(empty($userName)){
                continue;
            }

            if($installationDate == '1970-01-01'){
                $errorArr[] = '序号为' . ($key + 1) . '记录安装时间异常  用户：' . $userName;
                continue;
            }


            #判断是否已存在记录
            if(ReceivableAccount::query()
                ->leftJoin('receivable_account_address','receivable_account_address.reac_account_id','=','receivable_account.reac_id')
                ->where([
                    'receivable_account.reac_installation_date' => $installationDate,
                    'receivable_account.reac_user_name' => $userName,
                    'receivable_account.reac_user_mobile' => $userMobile,
                    'receivable_account.reac_installation_count' => $installationCount,
                    'receivable_account.reac_account_receivable' => $accountReceivable,
                    'receivable_account_address.reac_address' => $address[0] ?? ''
                ])->exists()){
                $errorArr[] = '序号为' . ($key + 1) . '的记录已存在  用户：' . $userName;
                continue;
            }

            #主数据
            $insert = [
                'reac_type' => 1,
                'reac_area' => $area,
                'reac_street' => $street,
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
            print_r($insert);die;
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
                $flowInsert[] = [
                    'reac_account_id' => $id,
                    'reac_datetime' => date('Y-m-d H:i:s'),
                    'reac_pay_way' => 5,
                    'reac_funds_received' => $fundsReceived,
                    'reac_type' => 1,
                    'reac_status' => 2,
                    'reac_remark' => '自动生成第一条欠款',
                    'reac_operator_id' => AuthLogic::$userId,
                ];

                if(count($flowInsert) >= 500){
                    ReceivableAccountFlow::query()->insert($flowInsert);
                    $flowInsert = [];
                }


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
}
