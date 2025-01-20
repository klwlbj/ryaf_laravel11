<?php

namespace App\Http\Logic;

use App\Models\Admin;
use App\Models\AdminPermission;
use App\Models\Department;

class AdminPermissionLogic extends BaseLogic
{
    public function getTreeList($params)
    {
        $query = AdminPermission::query();

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('adpe_name','like','%'.$params['keyword'].'%');
        }

        $list = $query
            ->orderBy('adpe_id','asc')->orderBy('adpe_sort','desc')->get()->toArray();

        $treeList = ToolsLogic::toTree($list,0,'adpe_id','adpe_parent_id');

        return ['list' => $treeList ?: $list];
    }

    public function getInfo($params)
    {
        $data = AdminPermission::query()->where(['adpe_id' => $params['id']])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        return $data;
    }

    public function add($params)
    {
        $parentLevel = adminPermission::query()->where(['adpe_id' => $params['parent_id'] ?? 0])->value('adpe_level') ?: 0;
        if($parentLevel >= 2 && $params['type'] == 1){
            ResponseLogic::setMsg('不可以添加3级菜单');
            return false;
        }

        $insertData = [
            'adpe_parent_id' => $params['parent_id'] ?? 0,
            'adpe_name' => $params['name'],
            'adpe_route' => $params['route'] ?? '',
            'adpe_level' => $parentLevel + 1,
            'adpe_type' => $params['type'] ?? 1,
            'adpe_sort' => $params['sort'] ?? 0,
            'adpe_status' => $params['status'] ?? 0,
        ];

        $id = AdminPermission::query()->insertGetId($insertData);
        if($id === false){
            ResponseLogic::setMsg('添加失败');
            return false;
        }

        return ['id' => $id];
    }

    public function update($params)
    {
        $parentLevel = adminPermission::query()->where(['adpe_id' => $params['parent_id']])->value('adpe_level') ?: 0;
        if($parentLevel >= 2 && $params['type'] == 1){
            ResponseLogic::setMsg('不可以添加3级菜单');
            return false;
        }

        $insertData = [
            'adpe_parent_id' => $params['parent_id'] ?? 0,
            'adpe_name' => $params['name'],
            'adpe_level' => $parentLevel + 1,
            'adpe_route' => $params['route'] ?? '',
            'adpe_type' => $params['type'] ?? 1,
            'adpe_sort' => $params['sort'] ?? 0,
            'adpe_status' => $params['status'] ?? 0,
        ];

        if(AdminPermission::query()->where(['adpe_id' => $params['id']])->update($insertData) === false){
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        return [];
    }

    public function delete($params)
    {
        if(AdminPermission::query()->where(['adpe_parent_id' => $params['id']])->exists()){
            ResponseLogic::setMsg('存在下级菜单，不能删除');
            return false;
        }

        AdminPermission::query()->where(['adpe_id' => $params['id']])->delete();
        return [];
    }
}
