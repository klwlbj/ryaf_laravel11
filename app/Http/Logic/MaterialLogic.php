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

        if(!empty($params['is_verify'])){
            $verifyIds = MaterialFlow::query()->where(['mafl_status' => 1])->select(['mafl_material_id'])->distinct()->pluck('mafl_material_id')->toArray();
            $query->whereIn('material.mate_id',$verifyIds);
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
                DB::raw("substring_index( group_concat( mafl_id ORDER BY mafl_datetime DESC ), ',', 1 ) AS mafl_id")
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


        #最后一次出库记录
        $lastOutFlowQuery = MaterialFlow::query()
            ->whereIn('mafl_material_id',$ids)
            ->where(['mafl_type' => 2])
            ->select([
                'mafl_material_id',
                DB::raw("substring_index( group_concat( mafl_id ORDER BY mafl_datetime DESC ), ',', 1 ) AS mafl_id")
            ])
            ->groupBy(['mafl_material_id']);

        #最后一次出库记录
        $lastOutFlowArr = MaterialFlow::query()
            ->joinSub($lastOutFlowQuery,'sub','material_flow.mafl_id','=','sub.mafl_id')
            ->leftJoin('admin','admin.admin_id','=','material_flow.mafl_apply_user_id')
            ->select([
                'material_flow.mafl_id',
                'admin.admin_name as mafl_apply_user_name',
                'material_flow.mafl_material_id',
                'material_flow.mafl_datetime',
                'material_flow.mafl_number'
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

        #获取未确认的出入库记录
        $flowArr = MaterialFlow::query()
            ->whereIn('mafl_material_id',$ids)
            ->where(['mafl_status' => 1])
            ->select([
                'mafl_material_id',
                DB::raw('count(mafl_id) as count')
            ])->groupBy(['mafl_material_id'])->get()->keyBy('mafl_material_id')->toArray();

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
            $value['last_out_flow'] = $lastOutFlowArr[$value['mate_id']] ?? [];
            $value['mate_price'] = bcdiv($value['mate_price_tax'],1 + $value['mate_tax']/100,2);
            $value['mate_invoice_type_msg'] = Material::$invoiceTypeArr[$value['mate_invoice_type']] ?? '未确认';
            $value['flow_count'] = $flowArr[$value['mate_id']]['count'] ?? 0;
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
            'mate_price_tax' => $params['price_tax'] ?? 0,
            'mate_tax' => $params['tax'] ?? 1.13,
            'mate_invoice_type' => $params['invoice_type'] ?? 1,
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
            'mate_price_tax' => $params['price_tax'] ?? 0,
            'mate_tax' => $params['tax'] ?? 1.13,
            'mate_invoice_type' => $params['invoice_type'] ?? 1,
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
//        $page = $params['page'] ?? 1;
//        $pageSize = $params['page_size'] ?? 10;
//        $point = ($page - 1) * $pageSize;

        $query = MaterialDetail::query()
//            ->leftJoin('warehouse','waho_id','=','material_detail.made_warehouse_id')
            ->leftJoin('material_flow','material_detail.made_in_id','=','material_flow.mafl_id')
            ->where(['made_status' => 1]);

        if(isset($params['material_id']) && $params['material_id']){
            $query->where(['made_material_id' => $params['material_id']]);
        }

//        $total = $query->count();

        $list = $query
            ->select([
                'material_flow.mafl_datetime as datetime',
                'material_flow.mafl_expire_date as expire_date',
                'material_flow.mafl_production_date as production_date',
                'material_flow.mafl_number as number',
                DB::raw("count(material_detail.made_id) as count"),
//                'warehouse.waho_name as made_warehouse_name',
            ])
            ->orderBy('mafl_id','desc')
            ->groupBy(['material_flow.mafl_id'])
            ->get()->toArray();

        foreach ($list as $key => &$value){
            $value['is_expire'] = ((strtotime($value['expire_date']) - time()) <= 60*60*24*30) ? 1 : 0;
        }

        unset($value);

        return [
//            'total' => $total,
            'list' => $list,
        ];
    }

    public function reportExport($params)
    {
        $startStr = date('Y.m.d',strtotime($params['start_date']));
        $endStr = date('Y.m.d',strtotime($params['end_date']));

        $params['start_date'] = $params['start_date'] . ' 00:00:00';
        $params['end_date'] = $params['end_date'] . ' 23:59:59';


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
                'mate_unit',
                'mate_price_tax',
                'mate_tax',
                'mate_invoice_type'
            ])->orderBy('mate_is_account','desc')->orderBy('mate_sort','desc')->get()->toArray();

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
                DB::raw("COALESCE(sum(IF(mafl_type=2,mafl_number,NULL)),0) as out_count"),
                DB::raw(Db::raw("(COALESCE(sum(IF(mafl_type=1,mafl_number,NULL)),0) - COALESCE(sum(IF(mafl_type=2,mafl_number,NULL)),0)) as count")),
            ])->groupBy(['mafl_material_id'])->get()->keyBy('mafl_material_id')->toArray();

//        print_r($startCountArr);die;

        #本期入库数
        $currentCountArr = MaterialFlow::query()
            ->whereIn('mafl_material_id',$ids)
            ->where('mafl_datetime','>=',$params['start_date'])
            ->where('mafl_datetime','<=',$params['end_date'])
            ->select([
                'mafl_id',
                'mafl_material_id',
                'mafl_type',
                'mafl_number',
                'mafl_price_tax',
                'mafl_tax',
                'mafl_invoice_type'
            ])->get()->groupBy('mafl_material_id')->toArray();

        $row = 2;
        $index = 1;
        $firstList = [];
        $secondList = [];
        foreach ($materialList as $key => $value){
            #获取初始入库记录
            $inFlowList = MaterialFlow::query()
            ->joinSub(MaterialDetail::query()
                ->where(['made_material_id' => $value['mate_id']])
                ->select([
                    'made_id',
                    'made_in_id',
                ])
                ->offset($currentCountArr[$value['mate_id']]['out_count'] ?? 0)
                ->limit($startCountArr[$value['mate_id']]['count'] ?? 0),'sub','sub.made_in_id','=','material_flow.mafl_id')
            ->select([
                'mafl_id',
                'mafl_price_tax',
                'mafl_tax',
                'mafl_invoice_type',
                DB::raw("count(sub.made_id) as count")
            ])->groupBy(['sub.made_in_id'])->get()->toArray();

            $inFlowArr = [];
            foreach ($inFlowList as $flowItem){
                $flowKey = $flowItem['mafl_price_tax'] . '_' . $flowItem['mafl_tax'] . '_' . $flowItem['mafl_invoice_type'];
                if(!isset($inFlowArr[$flowKey])){
                    $inFlowArr[$flowKey] = [
                        'count' => 0,
                        'in_count' => 0,
                        'out_count' => 0,
                        'tax' => $flowItem['mafl_tax'],
                        'price' => bcdiv($flowItem['mafl_price_tax'],1 + $flowItem['mafl_tax']/100),
                        'price_tax' => $flowItem['mafl_price_tax'],
                        'invoice_type' => $flowItem['mafl_invoice_type'],
                    ];
                }

                $inFlowArr[$flowKey]['count'] += $flowItem['count'];
            }

            #获取该物品本期出入库
            $currentFlowList = $currentCountArr[$value['mate_id']] ?? [];
            foreach ($currentFlowList as $flowItem){
                #如果是入库
                if($flowItem['mafl_type'] == 1){
                    $flowKey = $flowItem['mafl_price_tax'] . '_' . $flowItem['mafl_tax'] . '_' . $flowItem['mafl_invoice_type'];
                    if(!isset($inFlowArr[$flowKey])){
                        $inFlowArr[$flowKey] = [
                            'count' => 0,
                            'in_count' => 0,
                            'out_count' => 0,
                            'tax' => $flowItem['mafl_tax'],
                            'price' => bcdiv($flowItem['mafl_price_tax'],1 + $flowItem['mafl_tax']/100),
                            'price_tax' => $flowItem['mafl_price_tax'],
                            'invoice_type' => $flowItem['mafl_invoice_type'],
                        ];
                    }

                    $inFlowArr[$flowKey]['in_count'] += $flowItem['mafl_number'];
                }else{
                    #如果是出库 先获取涉及到哪几个入库记录
                    $relationInFlowList = MaterialDetail::query()
                        ->leftJoin('material_flow','material_flow.mafl_id','=','material_detail.made_in_id')
                        ->where(['made_out_id' => $flowItem['mafl_id']])
                        ->select([
                            'mafl_id',
                            'mafl_price_tax',
                            'mafl_tax',
                            'mafl_invoice_type',
                            DB::raw("count(material_detail.made_id) as count")
                        ])->groupBy(['material_detail.made_in_id'])->get()->toArray();



                    foreach ($relationInFlowList as $item){
                        $flowKey = $item['mafl_price_tax'] . '_' . $item['mafl_tax'] . '_' . $item['mafl_invoice_type'];

                        if(!isset($inFlowArr[$flowKey])){
                            $inFlowArr[$flowKey] = [
                                'count' => 0,
                                'in_count' => 0,
                                'out_count' => 0,
                                'tax' => $item['mafl_tax'],
                                'price' => bcdiv($item['mafl_price_tax'],1 + $item['mafl_tax']/100),
                                'price_tax' => $item['mafl_price_tax'],
                                'invoice_type' => $item['mafl_invoice_type'],
                            ];
                        }

                        $inFlowArr[$flowKey]['out_count'] += $item['count'];
                    }
                }


            }

            if(empty($inFlowArr)){
                $flowKey = $value['mate_price_tax'] . '_' . $value['mate_tax'] . '_' . $value['mate_invoice_type'];

                $inFlowArr[$flowKey] = [
                    'count' => 0,
                    'in_count' => 0,
                    'out_count' => 0,
                    'tax' => $value['mate_tax'],
                    'price' => bcdiv($value['mate_price_tax'],1 + $value['mate_tax']/100),
                    'price_tax' => $value['mate_price_tax'],
                    'invoice_type' => $value['mate_invoice_type'],
                ];

//                print_r($value);die;
            }

//            print_r($inFlowArr);die;

            foreach ($inFlowArr as $k => $flowItem){
                $priceInfoArr = explode('_',$k);
                $tag = floatval($priceInfoArr[0]) . '-' . floatval($priceInfoArr[1]) . '%';
                if($value['mate_is_account'] == 1){
                    $firstList[] = [
                        $index,
                        $value['mate_name'] . '(' . $tag . ')',
                        implode("\n",array_column($specificationArr[$value['mate_id']] ?? [],'masp_name')),
                        $value['mate_manufacturer_name'],
                        $value['mate_unit'],
                        $flowItem['price_tax'],
                        $flowItem['count'] ?? 0,
                        bcmul($flowItem['price_tax'],$flowItem['count'],2),
                        round(bcdiv(bcmul($flowItem['price_tax'],$flowItem['count'],2),1 + $flowItem['tax']/100,3),2),
                        $flowItem['in_count'] ?? 0,
                        bcmul($flowItem['price_tax'],$flowItem['in_count'],2),
                        round(bcdiv(bcmul($flowItem['price_tax'],$flowItem['in_count'],2),1 + $flowItem['tax']/100,3),2),
                        $flowItem['out_count'] ?? 0,
                        bcmul($flowItem['price_tax'],$flowItem['out_count'],2),
                        round(bcdiv(bcmul($flowItem['price_tax'],$flowItem['out_count'],2),1 + $flowItem['tax']/100,3),2),
                        ($flowItem['count'] ?? 0) + (($flowItem['in_count'] ?? 0) - ($flowItem['out_count'] ?? 0)),
                        bcmul($flowItem['price_tax'],$flowItem['count'] + $flowItem['in_count'] -  $flowItem['out_count'],2),
                        round(bcdiv(bcmul($flowItem['price_tax'],$flowItem['count'] + $flowItem['in_count'] -  $flowItem['out_count'],2),1 + $flowItem['tax']/100,3),2),
                    ];
                }else{
                    $secondList[] = [
                        $index,
                        $value['mate_name'] . '(' . $tag . ')',
                        implode("\n",array_column($specificationArr[$value['mate_id']] ?? [],'masp_name')),
                        $value['mate_manufacturer_name'],
                        $value['mate_unit'],
                        $flowItem['price_tax'],
                        $flowItem['count'] ?? 0,
                        bcmul($flowItem['price_tax'],$flowItem['count'],2),
                        round(bcdiv(bcmul($flowItem['price_tax'],$flowItem['count'],2),1 + $flowItem['tax']/100,3),2),
                        $flowItem['in_count'] ?? 0,
                        bcmul($flowItem['price_tax'],$flowItem['in_count'],2),
                        round(bcdiv(bcmul($flowItem['price_tax'],$flowItem['in_count'],2),1 + $flowItem['tax']/100,3),2),
                        $flowItem['out_count'] ?? 0,
                        bcmul($flowItem['price_tax'],$flowItem['out_count'],2),
                        round(bcdiv(bcmul($flowItem['price_tax'],$flowItem['out_count'],2),1 + $flowItem['tax']/100,3),2),
                        ($flowItem['count'] ?? 0) + (($flowItem['in_count'] ?? 0) - ($flowItem['out_count'] ?? 0)),
                        bcmul($flowItem['price_tax'],$flowItem['count'] + $flowItem['in_count'] -  $flowItem['out_count'],2),
                        round(bcdiv(bcmul($flowItem['price_tax'],$flowItem['count'] + $flowItem['in_count'] -  $flowItem['out_count'],2),1 + $flowItem['tax']/100,3),2),
                    ];
                }
                $index++;
                $row++;
            }
        }
//        print_r($secondList);die;
        $width = [];
        foreach ($title as $key => $value){
            if(in_array($value,['材料名称'])){
                $width[ExportLogic::getColumnName($key+1)] = 70;
            }elseif(in_array($value,['序号','规格类型','品牌','用量单位'])){
                $width[ExportLogic::getColumnName($key+1)] = 15;
            }elseif(in_array($value,['期末金额(不含税)'])){
                $width[ExportLogic::getColumnName($key+1)] = 40;
            }else{
                $width[ExportLogic::getColumnName($key+1)] = 30;
            }
        }

        $firstCount = count($firstList);
        $secondCount = count($secondList);
        $firstList[] = ['','','','','','小计','','','','','','','','','','','',''];
        $sumIndex = ['G','H','I','J','K','L','M','N','O','P','Q','R'];

        $sum = [];
        foreach ($sumIndex as $index){
            $sum[$index . ($firstCount + 2)] =  '=SUM(' . $index . '2:' . $index . ($firstCount + 1) . ')';
            $sum[$index . ($firstCount + 2 + $secondCount + 1)] =  '=SUM(' . $index . ($firstCount + 2 + 1) . ':'  . $index . ($firstCount + 2 + $secondCount) . ')';
        }


        $row++;

        $secondList[] = ['','','','','','小计','','','','','','','','','','','',''];
        $row++;
        $exportData = array_merge($firstList,$secondList);

        $config = [
            'color' => [ExportLogic::getColumnName(2) . '2:' . ExportLogic::getColumnName(2) . $row => 'FFFF0000','F'.($firstCount + 2).':R'. ($firstCount + 2) => 'FFFF0000','F'.($firstCount + 2 + $secondCount + 1).':R'. ($firstCount + 2 + $secondCount + 1) => 'FFFF0000'],
            'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . '1' => true],
            'width' => $width,
            'horizontal_center' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true],
            'wrap_text' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true],
            'freeze_pane' => ['F2' => true],
            'sum_func' => $sum,
        ];

        return ExportLogic::getInstance()->export($title,$exportData,'进销存报表',$config);
    }
}
