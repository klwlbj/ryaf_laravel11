<?php

namespace App\Http\Logic;

use App\Models\Material;
use App\Models\MaterialManufacturer;
use Illuminate\Support\Facades\DB;

class MaterialManufacturerLogic extends BaseLogic
{
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = MaterialManufacturer::query();

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('mama_name','like','%'.$params['keyword'].'%');
        }

        $total = $query->count();

        $list = $query
//            ->orderBy('sort','desc')
            ->orderBy('mama_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    public function getAllList($params)
    {
        $query = MaterialManufacturer::query();

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('mama_name','like','%'.$params['keyword'].'%');
        }

        return $query
            ->orderBy('mama_id','desc')
            ->get()->toArray();
    }

    public function getInfo($params)
    {
        $data = MaterialManufacturer::query()->where(['mama_id' => $params['id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        return $data;
    }

    public function add($params)
    {
        $insertData = [
            'mama_name' => $params['name'],
            'mama_remark' => $params['remark'] ?? '',
            'mama_status' => $params['status'] ?? 1,
        ];

        if(MaterialManufacturer::query()->where(['mama_name' => $params['name']])->exists()){
            ResponseLogic::setMsg('厂家名称已存在');
            return false;
        }

        $id = MaterialManufacturer::query()->insertGetId($insertData);
        if($id === false){
            ResponseLogic::setMsg('添加失败');
            return false;
        }

        return ['id' => $id];
    }

    public function update($params)
    {
        $insertData = [
            'mama_name' => $params['name'],
            'mama_remark' => $params['remark'] ?? '',
            'mama_status' => $params['status'] ?? 1
        ];

        if(MaterialManufacturer::query()->where('mama_id','<>',$params['id'])->where(['mama_name' => $params['name']])->exists()){
            ResponseLogic::setMsg('厂家名称已存在');
            return false;
        }

        if(MaterialManufacturer::query()->where(['mama_id' => $params['id']])->update($insertData) === false){
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        return [];
    }

    public function delete($params)
    {
        if(Material::query()->where(['mate_manufacturer_id',$params['id']])->exists()){
            ResponseLogic::setMsg('该厂家下存在物品，请删除物品后再删除厂家');
            return false;
        }

        MaterialManufacturer::query()->where(['mama_id' => $params['id']])->delete();
        return [];
    }
}
