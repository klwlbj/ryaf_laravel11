<?php

namespace App\Http\Logic;

use App\Models\MaterialCategory;
use App\Models\MaterialSpecification;
use Illuminate\Support\Facades\DB;

class MaterialCategoryLogic extends BaseLogic
{
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = MaterialCategory::query();

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('maca_name','like','%'.$params['keyword'].'%');
        }

        if(isset($params['is_deliver']) && $params['is_deliver']){
            $query->where(['maca_is_deliver' => $params['is_deliver']]);
        }

        $total = $query->count();

        $list = $query
            ->orderBy('maca_sort','desc')
            ->orderBy('maca_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    public function getAllList($params)
    {
        $query = MaterialCategory::query()->where(['maca_status' => 1]);

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('maca_name','like','%'.$params['keyword'].'%');
        }

        if(in_array(AuthLogic::$userId,MaterialLogic::$onlyAccessory)){
            $query->where(['maca_id' => 2]);
        }

        return $query
            ->orderBy('maca_sort','desc')
            ->orderBy('maca_id','asc')
            ->get()->toArray();
    }

    public function getInfo($params)
    {
        $data = MaterialCategory::query()->where(['maca_id' => $params['id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        return $data;
    }

    public function add($params)
    {
        $insertData = [
            'maca_name' => $params['name'],
            'maca_sort' => $params['sort'] ?? 0,
            'maca_remark' => $params['remark'] ?? '',
            'maca_status' => $params['status'] ?? 1,
        ];

        if(MaterialCategory::query()->where(['maca_name' => $params['name']])->exists()){
            ResponseLogic::setMsg('厂家名称已存在');
            return false;
        }

        $id = MaterialCategory::query()->insertGetId($insertData);
        if($id === false){
            ResponseLogic::setMsg('添加失败');
            return false;
        }

        return ['id' => $id];
    }

    public function update($params)
    {
        $insertData = [
            'maca_name' => $params['name'],
            'maca_sort' => $params['sort'] ?? 0,
            'maca_remark' => $params['remark'] ?? '',
            'maca_status' => $params['status'] ?? 1
        ];

        if(MaterialCategory::query()->where('maca_id','<>',$params['id'])->where(['maca_name' => $params['name']])->exists()){
            ResponseLogic::setMsg('厂家名称已存在');
            return false;
        }

        if(MaterialCategory::query()->where(['maca_id' => $params['id']])->update($insertData) === false){
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        return [];
    }

    public function delete($params)
    {
        if(MaterialCategory::query()->where(['mate_category_id',$params['id']])->exists()){
            ResponseLogic::setMsg('该分类下存在物品，请删除物品后再删除分类');
            return false;
        }
        MaterialCategory::query()->where(['maca_id' => $params['id']])->delete();

        MaterialSpecification::query()->where(['masp_category_id' => $params['id']])->delete();
        return [];
    }
}
