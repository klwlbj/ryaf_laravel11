<?php

namespace App\Http\Logic;

use App\Models\Admin;
use App\Models\AdminPermissionRelation;
use App\Models\ApprovalProcess;
use App\Models\Department;
use Illuminate\Support\Facades\Cache;
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

        setcookie(
            'X-Token',         // Cookie 名称（必需）
            $token,   // Cookie 值（默认空字符串）
            [
                'expires' => (time() + 86400),     // 过期时间（Unix 时间戳，0 表示会话 Cookie）
                'path' => "/",    // 生效路径（默认当前目录）
                'domain' => '',  // 生效域名（默认当前域名）
                'secure' => true, // 仅通过 HTTPS 传输（默认 false）
                'httponly' => true,// 禁止 JavaScript 访问（默认 false）
                'samesite' => 'Strict'
            ]
        );

        $menu = AdminPermissionRelation::getMenu($data['admin_id']);
        $permission = AdminPermissionRelation::getPermissionArr($data['admin_id']);

        $data['department'] = Department::query()
            ->where(['depa_id' => $data['admin_department_id']])
            ->select([
                'depa_name','depa_id','depa_leader_id'
            ])
            ->first();

        return ['token' => $token, 'menu' => $menu,'permission' => $permission,'admin' => $data];
    }

    public function logout($params)
    {
        setcookie(
            'X-Token',         // Cookie 名称（必需）
            '',   // Cookie 值（默认空字符串）
            [
                'expires' => (time() - 86400),     // 过期时间（Unix 时间戳，0 表示会话 Cookie）
                'path' => "/",    // 生效路径（默认当前目录）
                'domain' => '',  // 生效域名（默认当前域名）
                'secure' => true, // 仅通过 HTTPS 传输（默认 false）
                'httponly' => true,// 禁止 JavaScript 访问（默认 false）
                'samesite' => 'Strict'
            ]
        );

        Cache::delete('admin_' .AuthLogic::$userId . '_token');

        return [];
    }

    public function resetPassword($params)
    {
        $data = Admin::query()->where(['admin_id' => AuthLogic::$userId])->first();
        if(!$data){
            ResponseLogic::setMsg('用户不存在');
            return false;
        }

        $data = $data->toArray();

        if($data['admin_pwd'] != $params['password']){
            ResponseLogic::setMsg('原密码不正确');
            return false;
        }

        if($params['new_password'] != $params['confirm_password']){
            ResponseLogic::setMsg('确认密码跟新密码不一致');
            return false;
        }

        if(Admin::query()->where(['admin_id' => AuthLogic::$userId])->update(['admin_pwd' => $params['new_password']]) === false){
            ResponseLogic::setMsg('修改密码失败');
            return false;
        }

        return [];
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

        if(empty($params['all'])){
            $query->where('admin_department_id','>',0);
        }

        return $query
            ->select(['admin_id','admin_name','admin_mobile'])
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

        if(!empty($params['permission'])){
            $permissionArr = explode(',',$params['permission']);
        }else{
            $permissionArr = [];
        }


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

        if(!empty($permissionInsertData)){
            if(AdminPermissionRelation::query()->insert($permissionInsertData) === false){
                DB::rollBack();
                ResponseLogic::setMsg('添加权限失败');
                return false;
            }
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

        if(!empty($params['permission'])){
            $permissionArr = explode(',',$params['permission']);

            $permissionInsertData = [];
            foreach ($permissionArr as $key => $value){
                $permissionInsertData[] = [
                    'adpe_admin_id' => $params['id'],
                    'adpe_permission_id' => $value
                ];
            }
        }else{
            $permissionInsertData = [];
        }


        DB::beginTransaction();
        if(Admin::query()->where('admin_id',$params['id'])->update($insertData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        AdminPermissionRelation::query()->where(['adpe_admin_id' => $params['id']])->delete();
        if(!empty($permissionInsertData)){
            if(AdminPermissionRelation::query()->insert($permissionInsertData) === false){
                DB::rollBack();
                ResponseLogic::setMsg('更新权限失败');
                return false;
            }
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

    public function getBacklogCount($params)
    {
        $waitApprovalCount = ApprovalProcess::query()
            ->leftJoin('approval','approval.appr_id','=','approval_process.appr_approval_id')
            ->leftJoin('admin','admin_id','=','approval.appr_admin_id')
            ->where([
                'approval_process.appr_admin_id' => AuthLogic::$userId,
                'approval_process.appr_status' => 2,
                'approval.appr_status' => 1,
            ])->count() ?: 0;

        return [
            'wait_approval_count' => $waitApprovalCount,
        ];
    }
}
