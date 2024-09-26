<?php

namespace App\Http\Logic;

use App\Models\InstallationRegister;
use App\Models\InstallationRegisterAddress;
use App\Models\Node;
use Illuminate\Support\Facades\DB;

class InstallationRegisterLogic extends BaseLogic
{
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = InstallationRegister::query()
        ->leftJoin('node','node.node_id','=','installation_register.inre_node_id');

        if(!empty($params['node_id'])){
            $childIds = Node::getNodeChild($params['node_id']);
            $query->whereIn('inre_node_id',$childIds);
        }

        $total = $query->count();
        $list = $query
            ->select([
                'installation_register.*',
                'node.node_name as inre_node_name',
            ])
            ->orderBy('inre_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        $ids = array_column($list,'inre_id');

        $addressGroup = InstallationRegisterAddress::query()->whereIn('inre_register_id',$ids)->select([
            'inre_register_id',
            'inre_code as code',
            'inre_standard_address as standard_address',
            'inre_addr_generic_name as addr_generic_name',
            'inre_addr_room as addr_room',
            'inre_install_location as install_location',
        ])->get()->groupBy('inre_register_id')->toArray();

        foreach ($list as $key => &$value){
            $value['address_list'] = $addressGroup[$value['inre_id']] ?? [];
            $value['inre_pay_way_msg'] =  InstallationRegister::$payWayArr[$value['inre_pay_way']] ?? '';
        }

        unset($value);

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

    public function update($params)
    {
        $addressList = ToolsLogic::jsonDecode($params['address_list']);
        if(empty($addressList)){
            ResponseLogic::setMsg('地址不能为空');
            return false;
        }

        $updateData = [
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

        if(InstallationRegister::query()->where(['inre_id' => $params['id']])->update($updateData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        $addressInsertData = [];
        foreach ($addressList as $key => $value){
            $addressInsertData[] = [
                'inre_register_id' => $params['id'],
                'inre_code' => $value['code'],
                'inre_standard_address' => $value['standard_address'],
                'inre_addr_generic_name' => $value['addr_generic_name'],
                'inre_install_location' => $value['install_location'],
            ];
        }
        #删除原本地址
        InstallationRegisterAddress::query()->where(['inre_register_id' => $params['id']])->delete();

        if(InstallationRegisterAddress::query()->insert($addressInsertData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('添加地址失败');
            return false;
        }

        DB::commit();

        return [];
    }

    public function getInfo($params)
    {
        $data = InstallationRegister::query()->where('inre_id',$params['id'])->first();

        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $data = $data->toArray();

        $data['address_list'] = InstallationRegisterAddress::query()
            ->where(['inre_register_id'=>$params['id']])
            ->select([
                'inre_code as code',
                'inre_standard_address as standard_address',
                'inre_addr_generic_name as addr_generic_name',
                'inre_install_location as install_location',
            ])
            ->get()->toArray();

        $data['node_arr'] = Node::getNodeParent($data['inre_node_id']);

        return $data;
    }

    public function delete($params)
    {
        $data = InstallationRegister::query()->where('inre_id',$params['id'])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $data = $data->toArray();

        if($data['inre_status'] == 2){
            ResponseLogic::setMsg('已安装的不能删除');
            return false;
        }

        DB::beginTransaction();

        if(InstallationRegister::query()->where('inre_id',$params['id'])->delete() === false){
            DB::rollBack();
            ResponseLogic::setMsg('删除失败');
            return false;
        }

        if(InstallationRegisterAddress::query()->where(['inre_register_id'=>$params['id']])->delete() === false){
            DB::rollBack();
            ResponseLogic::setMsg('删除地址失败');
            return false;
        }

        DB::commit();

        return [];
    }
}
