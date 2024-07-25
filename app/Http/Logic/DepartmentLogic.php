<?php

namespace App\Http\Logic;

use App\Models\Admin;
use App\Models\Department;

class DepartmentLogic extends BaseLogic
{
    public function getTreeList($params)
    {
        $query = Department::query()
        ->leftJoin('admin','admin.admin_id','=','department.depa_leader_id');

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('depa_name','like','%'.$params['keyword'].'%');
        }

        if(!empty($params['status'])){
            $query->where('depa_status',$params['status']);
        }

        $list = $query
            ->select([
                'department.*',
                'admin.admin_name as depa_leader_name'
            ])
            ->orderBy('depa_id','asc')->orderBy('depa_sort','desc')->get()->toArray();

        $treeList = ToolsLogic::toTree($list,0,'depa_id','depa_parent_id');

        return ['list' => $treeList ?: $list];
    }

    public function getInfo($params)
    {
        $data = Department::query()->where(['depa_id' => $params['id']])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        return $data;
    }

    public function add($params)
    {
        $insertData = [
            'depa_parent_id' => $params['parent_id'] ?? 0,
            'depa_name' => $params['name'],
            'depa_sort' => $params['sort'] ?? 0,
            'depa_status' => $params['status'] ?? 0,
            'depa_leader_id' => $params['leader_id'] ?? 0,
        ];

        if(Department::query()->where(['depa_name' => $params['name']])->exists()){
            ResponseLogic::setMsg('部门名称已存在');
            return false;
        }

        $id = Department::query()->insertGetId($insertData);
        if($id === false){
            ResponseLogic::setMsg('添加失败');
            return false;
        }

        return ['id' => $id];
    }

    public function update($params)
    {
        $insertData = [
            'depa_parent_id' => $params['parent_id'] ?? 0,
            'depa_name' => $params['name'],
            'depa_sort' => $params['sort'] ?? 0,
            'depa_status' => $params['status'] ?? 0,
            'depa_leader_id' => $params['leader_id'] ?? 0,
        ];

        if(Department::query()->where('depa_id','<>',$params['id'])->where(['depa_name' => $params['name']])->exists()){
            ResponseLogic::setMsg('部门名称已存在');
            return false;
        }

        if(Department::query()->where(['depa_id' => $params['id']])->update($insertData) === false){
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        return [];
    }

    public function delete($params)
    {
        if(Department::query()->where(['depa_parent_id' => $params['id']])->exists()){
            ResponseLogic::setMsg('存在下级部门，不能删除');
            return false;
        }

        if(Admin::query()->where(['admin_part_id' => $params['id']])->exists()){
            ResponseLogic::setMsg('部门下存在人员，不能删除');
            return false;
        }

        Admin::query()->where(['depa_id' => $params['id']])->delete();
        return [];
    }
}
