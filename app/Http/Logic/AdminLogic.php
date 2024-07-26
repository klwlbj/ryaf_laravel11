<?php

namespace App\Http\Logic;

use App\Models\Admin;
use App\Models\AdminPermissionRelation;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class AdminLogic extends BaseLogic
{
    public function login($params)
    {
        $data = Admin::query()->where(['admin_mobile' => $params['mobile'],'admin_enabled' => 1])->first();
        if(!$data){
            ResponseLogic::setMsg('账号不存在');
            return false;
        }
        $data = $data->toArray();
        if($data['admin_pwd'] != $params['password']){
            ResponseLogic::setMsg('密码不正确');
            return false;
        }

        $token = AuthLogic::getToken($data);
        $menu = AdminPermissionRelation::getMenu($data['admin_id']);
        $permission = AdminPermissionRelation::getPermissionArr($data['admin_id']);

        return ['token' => $token, 'menu' => $menu,'permission' => $permission];
    }

    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = Admin::query();

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('admin_name','like','%'.$params['keyword'].'%');
        }

        if(isset($params['department_id']) && $params['department_id']){
            $query->where(['admin_department_id' => $params['department_id']]);
        }

//        if(isset($params['is_leader']) && $params['is_leader']){
//            $query->where(['admin_is_leader' => $params['is_leader']]);
//        }

        $total = $query->count();

        $list = $query
            ->orderBy('admin_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        foreach ($list as $key => &$value){
            $value['admin_department_name'] = Department::getDepartmentStr($value['admin_department_id']);
        }

        unset($value);
        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    public function getAllList($params)
    {
        $query = Admin::query()
            ->where(['admin_enabled' => 1]);

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('admin_name','like','%'.$params['keyword'].'%');
        }

        return $query
            ->get()->toArray();
    }

    public function add($params)
    {
        $insertData = [
            'admin_department_id' => $params['department_id'] ?? 0,
            'admin_name' => $params['name'],
            'admin_mobile' => $params['mobile'],
            'admin_pwd' => $params['password'] ?? '123457',
            'admin_auths' => '',
            'admin_enabled' => $params['status'] ?? 1,
//            'admin_is_leader' => $params['is_leader'] ?? 0,
        ];

        if(Admin::query()->where(['admin_name' => $params['name']])->exists()){
            ResponseLogic::setMsg('部门名称已存在');
            return false;
        }

        $permissionArr = explode(',',$params['permission']);

        DB::beginTransaction();
        $id = Admin::query()->insertGetId($insertData);
        if($id === false){
            DB::rollBack();
            ResponseLogic::setMsg('添加失败');
            return false;
        }

        $permissionInsertData = [];
        foreach ($permissionArr as $key => $value){
            $permissionInsertData[] = [
                'adpe_admin_id' => $id,
                'adpe_permission_id' => $value
            ];
        }

        if(AdminPermissionRelation::query()->insert($permissionInsertData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('添加权限失败');
            return false;
        }

        DB::commit();
        return ['id' => $id];
    }

    public function update($params)
    {
        $insertData = [
            'admin_department_id' => $params['department_id'] ?? 0,
            'admin_name' => $params['name'],
            'admin_mobile' => $params['mobile'],
            'admin_enabled' => $params['status'] ?? 1,
//            'admin_is_leader' => $params['is_leader'] ?? 0,
        ];

        if(!empty($params['password'])){
            $insertData['admin_pwd'] = $params['password'];
        }

        if(Admin::query()->where(['admin_name' => $params['name']])->where('admin_id','<>',$params['id'])->exists()){
            ResponseLogic::setMsg('部门名称已存在');
            return false;
        }

        $permissionArr = explode(',',$params['permission']);

        $permissionInsertData = [];
        foreach ($permissionArr as $key => $value){
            $permissionInsertData[] = [
                'adpe_admin_id' => $params['id'],
                'adpe_permission_id' => $value
            ];
        }

        DB::beginTransaction();
        if(Admin::query()->where('admin_id',$params['id'])->update($insertData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        AdminPermissionRelation::query()->where(['adpe_admin_id' => $params['id']])->delete();
        if(AdminPermissionRelation::query()->insert($permissionInsertData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新权限失败');
            return false;
        }

        DB::commit();
        return [];
    }

    public function getInfo($params)
    {
        $data = Admin::query()->where(['admin_id' => $params['id']])->first();

        if(!$data){
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        $data['permission'] = AdminPermissionRelation::query()
            ->where(['adpe_admin_id' => $params['id']])
            ->select(['adpe_permission_id'])->pluck('adpe_permission_id') ?: [];

        return $data;
    }

    public function delete($params)
    {
        Admin::query()->where('admin_id',$params['id'])->delete();

        return [];
    }
}
