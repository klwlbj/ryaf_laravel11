<?php

namespace App\Http\Logic;

use App\Http\Logic\Excel\ExportLogic;
use App\Models\Material;
use App\Models\MaterialDetail;
use App\Models\MaterialSpecificationRelation;
use Illuminate\Support\Facades\DB;

class MaterialLogic extends BaseLogic
{
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = Material::query()
            ->leftJoin('material_manufacturer','material.mate_manufacturer_id','=','material_manufacturer.mama_id')
            ->leftJoin('material_category','material.mate_category_id','=','material_category.maca_id')
        ;

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('mate_name','like','%'.$params['keyword'].'%');
        }

        if(isset($params['category_id']) && $params['category_id']){
            $query->where(['material.mate_category_id' => $params['category_id']]);
        }

        if(isset($params['is_deliver']) && $params['is_deliver']){
            $query->where(['material.mate_is_deliver' => $params['is_deliver']]);
        }

        if(isset($params['manufacturer_id']) && $params['manufacturer_id']){
            $query->where(['material.mate_manufacturer_id' => $params['manufacturer_id']]);
        }

        if(isset($params['specification_id']) && $params['specification_id']){
            $query->where(['material.mate_specification_id' => $params['specification_id']]);
        }

        $total = $query->count();

        if(!empty($params['export'])){
            $list = $query
                ->select([
                    'material.*',
                    'material_manufacturer.mama_name as mate_manufacturer_name',
                    'material_category.maca_name as mate_category_name',
                ])
                ->orderBy('mate_sort','desc')
                ->orderBy('mate_id','desc')
                ->get()->toArray();
        }else{
            $list = $query
                ->select([
                    'material.*',
                    'material_manufacturer.mama_name as mate_manufacturer_name',
                    'material_category.maca_name as mate_category_name',
                ])
                ->orderBy('mate_sort','desc')
                ->orderBy('mate_id','desc')
                ->offset($point)->limit($pageSize)->get()->toArray();
        }


        $ids = array_column($list,'mate_id');

        $expireArr = MaterialDetail::query()
            ->whereIn('made_material_id',$ids)
            ->where(['made_status' => 1])
            ->whereRaw("DATEDIFF(made_expire_date,NOW()) <= 30")
            ->select([
                'made_material_id',
                DB::raw('count(made_material_id) as count')
            ])->groupBy(['made_material_id'])->get()->pluck('count','made_material_id')->toArray();

        $specificationArr = MaterialSpecificationRelation::query()
            ->leftJoin('material_specification','material_specification.masp_id','=','material_specification_relation.masp_specification_id')
            ->whereIn('masp_material_id',$ids)
            ->orderBy('material_specification.masp_sort','desc')
            ->orderBy('material_specification.masp_id','desc')
            ->select([
                'masp_material_id',
                'material_specification.masp_name'
            ])->get()->groupBy('masp_material_id')->toArray();


        foreach ($list as $key => &$value){
            if(isset($expireArr[$value['mate_id']])){
                $value['expire_count'] = $expireArr[$value['mate_id']];
            }else{
                $value['expire_count'] = 0;
            }

            if(isset($specificationArr[$value['mate_id']])){
                $value['mate_specification_name'] = array_column($specificationArr[$value['mate_id']],'masp_name');
            }else{
                $value['mate_specification_name'] = [];
            }
        }

        unset($value);

        if(!empty($params['export'])){
            return $this->export($list);
        }

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    public function export($list)
    {
        $title = ['名称','厂家','类别','规格','单位','库存','预警'];

        $exportData = [];
        $config = [
            'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(7) . '1' => true],
            'width' => ['A'=>20,'B'=>20,'C'=>20,'D'=>20,'E'=>20,'F'=>20,'G'=>20,]
        ];
        $row = 2;
        foreach ($list as $key => $value){
            $exportData[] = [
                $value['mate_name'],
                $value['mate_manufacturer_name'],
                $value['mate_category_name'],
                implode("\n",$value['mate_specification_name']),
                $value['mate_unit'],
                $value['mate_number'],
                $value['mate_warning'],
            ];

            $row++;
        }
        $config['horizontal_center'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(7) . $row => true];
        $config['wrap_text'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(7) . $row => true];

        return ExportLogic::getInstance()->export($title,$exportData,'库存导出',$config);

    }

    public function getAllList($params)
    {
        $query = Material::query()->where(['mate_status' => 1]);

        if(isset($params['keyword']) && $params['keyword']){
            $query->where('mate_id_name','like','%'.$params['keyword'].'%');
        }

        return $query
            ->orderBy('mate_sort','desc')
            ->orderBy('mate_id','desc')
            ->get()->toArray();
    }

    public function getInfo($params)
    {
        $data = Material::query()->where(['mate_id' => $params['id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        $data['mate_specification_id'] = MaterialSpecificationRelation::query()
            ->where(['masp_material_id' => $params['id']])
            ->select(['masp_specification_id'])
            ->pluck('masp_specification_id')->toArray();

        return $data;
    }

    public function add($params)
    {
        $insertData = [
            'mate_manufacturer_id' => $params['manufacturer_id'],
            'mate_category_id' => $params['category_id'],
            'mate_name' => $params['name'],
            'mate_is_deliver' => $params['is_deliver'] ?? 0,
            'mate_number' => $params['number'] ?? 0,
            'mate_unit' => $params['unit'],
            'mate_warning' => $params['warning'] ?? 0,
            'mate_image' => $params['image'] ?? '',
            'mate_remark' => $params['remark'] ?? '',
            'mate_sort' => $params['sort'] ?? 0,
            'mate_status' => $params['status'] ?? 1,
            'mate_operator_id' => 2, #操作id  默认写死
        ];

//        if(Material::query()->where(['mate_name' => $params['name']])->exists()){
//            ResponseLogic::setMsg('物品名称已存在');
//            return false;
//        }

        $specificationIds = explode(',',$params['specification_id']);

        if(empty($specificationIds)){
            ResponseLogic::setMsg('规格不得为空');
            return false;
        }

        $id = Material::query()->insertGetId($insertData);
        if($id === false){
            ResponseLogic::setMsg('添加失败');
            return false;
        }

        #插入规格关系表
        $specificationInsert = [];
        foreach ($specificationIds as $specificationId){
            $specificationInsert[] = [
                'masp_material_id' => $id,
                'masp_specification_id' => $specificationId,
            ];
        }

        MaterialSpecificationRelation::query()->insert($specificationInsert);

        return ['id' => $id];
    }

    public function update($params)
    {
        $insertData = [
            'mate_manufacturer_id' => $params['manufacturer_id'],
            'mate_category_id' => $params['category_id'],
            'mate_name' => $params['name'],
            'mate_is_deliver' => $params['is_deliver'] ?? 0,
            'mate_unit' => $params['unit'],
            'mate_warning' => $params['warning'],
            'mate_image' => $params['image'] ?? '',
            'mate_remark' => $params['remark'] ?? '',
            'mate_sort' => $params['sort'] ?? 0,
            'mate_status' => $params['status'] ?? 1,
            'mate_operator_id' => 2, #操作id  默认写死
        ];

        $specificationIds = explode(',',$params['specification_id']);

        if(empty($specificationIds)){
            ResponseLogic::setMsg('规格不得为空');
            return false;
        }

//        if(Material::query()->where('mate_id','<>',$params['id'])->where(['mate_name' => $params['name']])->exists()){
//            ResponseLogic::setMsg('物品名称已存在');
//            return false;
//        }


        if(Material::query()->where(['mate_id' => $params['id']])->update($insertData) === false){
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        #删除关系表数据
        MaterialSpecificationRelation::query()->where(['masp_material_id' => $params['id']])->delete();
        #插入规格关系表
        $specificationInsert = [];
        foreach ($specificationIds as $specificationId){
            $specificationInsert[] = [
                'masp_material_id' => $params['id'],
                'masp_specification_id' => $specificationId,
            ];
        }

        MaterialSpecificationRelation::query()->insert($specificationInsert);

        Material::delCacheById($params['id']);

        return [];
    }

    public function delete($params)
    {
        if(Material::query()->where(['mate_id' => $params['id']])->where('number','>',0)->exists()){
            ResponseLogic::setMsg('该物品存在库存，请把库存出库后再删除');
            return false;
        }

        Material::query()->where(['mate_id' => $params['id']])->delete();
        Material::delCacheById($params['id']);
        return [];
    }

    public function getDetailList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = MaterialDetail::query()
            ->leftJoin('warehouse','waho_id','=','material_detail.made_warehouse_id')
            ->where(['made_status' => 1]);

        if(isset($params['material_id']) && $params['material_id']){
            $query->where(['made_material_id' => $params['material_id']]);
        }

        $total = $query->count();

        $list = $query
            ->select([
                'material_detail.*',
                'warehouse.waho_name as made_warehouse_name',
            ])
            ->orderBy('made_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        foreach ($list as $key => &$value){
            $value['is_expire'] = ((strtotime($value['made_expire_date']) - time()) <= 60*60*24*30) ? 1 : 0;
        }

        unset($value);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }
}
