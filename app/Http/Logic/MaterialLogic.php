<?php

namespace App\Http\Logic;

use App\Http\Logic\Excel\ExportLogic;
use App\Models\Material;
use App\Models\MaterialDetail;
use App\Models\MaterialFlow;
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

        #过期列表
        $expireList = Material::query()
            ->leftJoin('material_detail','material_detail.made_material_id','=','material.mate_id')
            ->where(['material.mate_status' => 1,'material_detail.made_status' => 1])
            ->whereRaw("DATEDIFF(material_detail.made_expire_date,NOW()) <= 30")
            ->select([
                'material.mate_id',
                'material.mate_name',
                DB::raw("GROUP_CONCAT(distinct material_detail.made_expire_date) as mate_expire_date"),
                DB::raw('count(material_detail.made_id) as expire_count')
            ])->groupBy(['material.mate_id'])
            ->get()
            ->keyBy('mate_id')
            ->toArray();

        if(!empty($params['is_expire'])){
            if(!empty($expireList)){
                $ids = array_column($expireList,'mate_id');
                $query->whereIn('material.mate_id',$ids);
            }
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

//        $expireArr = MaterialDetail::query()
//            ->whereIn('made_material_id',$ids)
//            ->where(['made_status' => 1])
//            ->whereRaw("DATEDIFF(made_expire_date,NOW()) <= 30")
//            ->select([
//                'made_material_id',
//                DB::raw('count(made_material_id) as count')
//            ])->groupBy(['made_material_id'])->get()->pluck('count','made_material_id')->toArray();

        #最后一次入库记录
        $lastInFlowQuery = MaterialFlow::query()
            ->whereIn('mafl_material_id',$ids)
            ->where(['mafl_type' => 1])
            ->select([
                'mafl_material_id',
                DB::raw("substring_index( group_concat( mafl_id ORDER BY mafl_id DESC ), ',', 1 ) AS mafl_id")
            ])
            ->groupBy(['mafl_material_id']);

        #最后一次入库记录
        $lastInFlowArr = MaterialFlow::query()
            ->joinSub($lastInFlowQuery,'sub','material_flow.mafl_id','=','sub.mafl_id')
            ->select([
                'material_flow.mafl_id',
                'material_flow.mafl_material_id',
                'material_flow.mafl_datetime',
                'material_flow.mafl_number',
                'material_flow.mafl_production_date',
                'material_flow.mafl_expire_date',
            ])
            ->get()->keyBy('mafl_material_id')->toArray();

        #规格列表
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
            if(isset($expireList[$value['mate_id']])){
                $value['expire_count'] = $expireList[$value['mate_id']]['expire_count'];
                $value['expire_date'] = $expireList[$value['mate_id']]['mate_expire_date'];
            }else{
                $value['expire_count'] = 0;
                $value['expire_date'] = '';
            }

            if(isset($specificationArr[$value['mate_id']])){
                $value['mate_specification_name'] = array_column($specificationArr[$value['mate_id']],'masp_name');
            }else{
                $value['mate_specification_name'] = [];
            }

            $value['last_in_flow'] = $lastInFlowArr[$value['mate_id']] ?? [];
        }

        unset($value);

        if(!empty($params['export'])){
            return $this->export($list);
        }

        return [
            'total' => $total,
            'list' => $list,
            'expire_list' => array_values($expireList),
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

        if(!empty($params['category_id'])){
            $query->where(['material.mate_category_id' => $params['category_id']]);
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

    public function getDetail($params)
    {
        $data = Material::query()
            ->leftJoin('material_manufacturer','material_manufacturer.mama_id','=','material.mate_manufacturer_id')
            ->leftJoin('material_category','material_category.maca_id','=','material.mate_category_id')
            ->where(['mate_id' => $params['id']])
            ->select([
                'mate_id',
                'mate_name',
                'mama_name as mate_manufacturer_name',
                'maca_name as mate_category_name',
                'mate_number',
                'mate_unit',
                'mate_warning',
                'mate_image'
            ])
            ->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        $data = $data->toArray();

        $data['mate_specification_name'] = MaterialSpecificationRelation::query()
            ->leftJoin('material_specification','material_specification.masp_id','=','material_specification_relation.masp_specification_id')
            ->where(['masp_material_id' => $params['id']])
            ->select(['masp_name'])
            ->pluck('masp_name')->toArray();

        #最后一次入库
        $lastInFlow = MaterialFlow::query()
            ->where(['mafl_material_id' => $params['id'],'mafl_type' => 1])
            ->select([
                'mafl_datetime',
                'mafl_number',
                'mafl_production_date',
                'mafl_expire_date'
            ])
            ->orderBy('mafl_datetime','desc')->first();

        $data['last_in_flow'] = ($lastInFlow) ? $lastInFlow->toArray() : null;

        #最后一次出库
        $lastOutFlow = MaterialFlow::query()
            ->leftJoin('admin as a1','material_flow.mafl_apply_user_id','=','a1.admin_id')
            ->leftJoin('admin as a2','material_flow.mafl_receive_user_id','=','a2.admin_id')
            ->where(['mafl_material_id' => $params['id'],'mafl_type' => 2])
            ->select([
                'a1.admin_name as mafl_apply_name',
                'a2.admin_name as mafl_receive_name',
                'mafl_datetime',
                'mafl_number',
                'mafl_purpose'
            ])
            ->orderBy('mafl_datetime','desc')->first();

        $data['last_out_flow'] = ($lastOutFlow) ? $lastOutFlow->toArray() : null;

        #获取临期和过期数据
        $data['expire_list'] = MaterialFlow::query()
            ->leftJoin('material_detail','material_detail.made_in_id','=','material_flow.mafl_id')
            ->where(['mafl_material_id' => $params['id'],'mafl_type' => 1,'material_detail.made_status' => 1])
            ->whereRaw("DATEDIFF(material_flow.mafl_expire_date,NOW()) <= 30")
            ->select([
                'material_flow.mafl_id',
                'material_flow.mafl_datetime',
                'material_flow.mafl_number',
                'material_flow.mafl_expire_date',
                DB::raw('count(material_detail.made_id) as expire_count')
            ])->groupBy(['material_flow.mafl_id'])->get()->toArray();


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

    public function reportExport($params)
    {
        $startStr = date('Y.m.d',strtotime($params['start_date']));
        $endStr = date('Y.m.d',strtotime($params['end_date']));

        $title = ['序号','材料名称','规格型号','品牌','用量单位','单价(元)',"期初数量\n({$startStr})","初始金额(元)","初期金额(不含税)","本期入库数\n(" . $startStr . '-' . $endStr . ')',"本期入库成本(元)\n(" . $startStr . '-' . $endStr . ')',"本期入库成本(不含税)","本期出库数\n(" . $startStr . '-' . $endStr . ')',"本期出库金额(元)","本期出库金额(不含税)","期末数量\n({$endStr})","期末金额(元)","期末金额(不含税)"];

        $exportData = [];

        $materialList = Material::query()
            ->leftJoin('material_manufacturer','material_manufacturer.mama_id','=','material.mate_manufacturer_id')
            ->where(['mate_status' => 1])
            ->select([
                'mate_id',
                'mate_name',
                'mate_is_account',
                'mama_name as mate_manufacturer_name',
                'mate_unit'
            ])->orderBy('mate_is_account','desc')->get()->toArray();

        $ids = array_column($materialList,'mate_id');

        $specificationArr = MaterialSpecificationRelation::query()
            ->leftJoin('material_specification','material_specification.masp_id','=','material_specification_relation.masp_specification_id')
            ->whereIn('masp_material_id',$ids)
            ->orderBy('material_specification.masp_sort','desc')
            ->orderBy('material_specification.masp_id','desc')
            ->select([
                'masp_material_id',
                'material_specification.masp_name'
            ])->get()->groupBy('masp_material_id')->toArray();

        #起始库存
        $startCountArr = MaterialFlow::query()
            ->whereIn('mafl_material_id',$ids)
            ->where('mafl_datetime','<',$params['start_date'])
            ->select([
                'mafl_material_id',
                DB::raw(Db::raw("(COALESCE(sum(IF(mafl_type=1,mafl_number,NULL)),0) - COALESCE(sum(IF(mafl_type=2,mafl_number,NULL)),0)) as count")),
            ])->groupBy(['mafl_material_id'])->get()->keyBy('mafl_material_id')->toArray();

        #本期入库数
        $currentCountArr = MaterialFlow::query()
            ->whereIn('mafl_material_id',$ids)
            ->where('mafl_datetime','>=',$params['start_date'])
            ->where('mafl_datetime','<=',$params['end_date'])
            ->select([
                'mafl_material_id',
                DB::raw(Db::raw("COALESCE(sum(IF(mafl_type=1,mafl_number,NULL)),0) as in_count")),
                DB::raw(Db::raw("COALESCE(sum(IF(mafl_type=2,mafl_number,NULL)),0) as out_count")),
            ])->groupBy(['mafl_material_id'])->get()->keyBy('mafl_material_id')->toArray();

        $row = 2;
        $firstList = [];
        $secondList = [];
        foreach ($materialList as $key => $value){
            if($value['mate_is_account'] == 1){
                $firstList[] = [
                    $key+1,
                    $value['mate_name'],
                    implode("\n",array_column($specificationArr[$value['mate_id']] ?? [],'masp_name')),
                    $value['mate_manufacturer_name'],
                    $value['mate_unit'],
                    '',
                    $startCountArr[$value['mate_id']]['count'] ?? 0,
                    '',
                    '',
                    $currentCountArr[$value['mate_id']]['in_count'] ?? 0,
                    '',
                    '',
                    $currentCountArr[$value['mate_id']]['out_count'] ?? 0,
                    '',
                    '',
                    ($startCountArr[$value['mate_id']]['count'] ?? 0) + (($currentCountArr[$value['mate_id']]['in_count'] ?? 0) - ($currentCountArr[$value['mate_id']]['out_count'] ?? 0)),
                    '',
                    ''
                ];
            }else{
                $secondList[] = [
                    $key+1,
                    $value['mate_name'],
                    implode("\n",array_column($specificationArr[$value['mate_id']] ?? [],'masp_name')),
                    $value['mate_manufacturer_name'],
                    $value['mate_unit'],
                    '',
                    $startCountArr[$value['mate_id']]['count'] ?? 0,
                    '',
                    '',
                    $currentCountArr[$value['mate_id']]['in_count'] ?? 0,
                    '',
                    '',
                    $currentCountArr[$value['mate_id']]['out_count'] ?? 0,
                    '',
                    '',
                    ($startCountArr[$value['mate_id']]['count'] ?? 0) + (($currentCountArr[$value['mate_id']]['in_count'] ?? 0) - ($currentCountArr[$value['mate_id']]['out_count'] ?? 0)),
                    '',
                    ''
                ];
            }
//            print_r($specificationArr[$value['mate_id']]);die;


            $row++;
        }

        $width = [];
        foreach ($title as $key => $value){
            if(in_array($value,['材料名称'])){
                $width[ExportLogic::getColumnName($key+1)] = 50;
            }elseif(in_array($value,['序号','规格类型','品牌','用量单位'])){
                $width[ExportLogic::getColumnName($key+1)] = 15;
            }else{
                $width[ExportLogic::getColumnName($key+1)] = 30;
            }

        }
        $firstList[] = ['','','','','','','','','','','','','','','','','',''];
        $row++;
        $exportData = array_merge($firstList,$secondList);

        $config = [
            'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . '1' => true],
            'width' => $width,
            'horizontal_center' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true],
            'wrap_text' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true],
        ];

        return ExportLogic::getInstance()->export($title,$exportData,'进销存报表',$config);
    }
}
