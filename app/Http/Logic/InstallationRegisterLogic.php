<?php

namespace App\Http\Logic;

use App\Models\InstallationRegister;
use App\Models\InstallationRegisterAddress;
use Illuminate\Support\Facades\DB;

class InstallationRegisterLogic extends BaseLogic
{
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = InstallationRegister::query();

        $total = $query->count();
        $list = $query->orderBy('inre_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        return ['list' => $list,'total' => $total];
    }

    public function add($params)
    {
        $addressList = ToolsLogic::jsonDecode($params['address_list']);
        if(empty($addressList)){
            ResponseLogic::setMsg('地址不能为空');
            return false;
        }

        $insertData = [
            'inre_datetime' => $params['datetime'],
            'inre_node_id' => $params['node_id'],
            'inre_user_name' => $params['user_name'],
            'inre_user_phone' => $params['user_phone'],
            'inre_user_type' => $params['user_type'],
            'inre_price' => $params['price'] ?? 0,
            'inre_install_count' => $params['install_count'],
            'inre_given_count' => $params['given_count'] ?? 0,
            'inre_pay_way' => $params['pay_way'],
            'inre_total_price' => $params['total_price'] ?? 0,
            'inre_remark' => $params['remark'] ?? '',
            'inre_delivery_count' => $params['delivery_count'] ?? 0,
            'inre_operator_id' => AuthLogic::$userId
        ];

        DB::beginTransaction();

        $id = InstallationRegister::query()->insertGetId($insertData);
        if($id === false){
            DB::rollBack();
            ResponseLogic::setMsg('添加失败');
            return false;
        }

        $addressInsertData = [];
        foreach ($addressList as $key => $value){
            $addressInsertData[] = [
                'inre_register_id' => $id,
                'inre_code' => $value['code'],
                'inre_standard_address' => $value['standard_address'],
                'inre_addr_generic_name' => $value['addr_generic_name'],
                'inre_install_location' => $value['install_location'],
            ];
        }

        if(InstallationRegisterAddress::query()->insert($addressInsertData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('添加地址失败');
            return false;
        }

        DB::commit();

        return [];
    }
}
