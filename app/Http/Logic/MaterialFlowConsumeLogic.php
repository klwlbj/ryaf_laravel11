<?php

namespace App\Http\Logic;

use App\Models\MaterialFlow;
use App\Models\MaterialFlowConsume;
use App\Models\MaterialSpecificationRelation;
use Illuminate\Support\Facades\DB;

class MaterialFlowConsumeLogic extends BaseLogic
{
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = MaterialFlow::query()
            ->leftJoin('material','material_flow.mafl_material_id','=','material.mate_id')
            ->leftJoin('admin as receive_user','material_flow.mafl_receive_user_id','=','receive_user.admin_id')
            ->leftJoin('admin as apply_user','material_flow.mafl_apply_user_id','=','apply_user.admin_id')
            ->where(['material_flow.mafl_type' => 2]);
        ;

        if(isset($params['material_id']) && $params['material_id']){
            $query->where(['material_flow.mafl_material_id' => $params['material_id']]);
        }

        if(!empty($params['start_date'])){
            $query->where('material_flow.mafl_datetime','>=',$params['start_date']);
        }

        if(!empty($params['end_date'])){
            $query->where('material_flow.mafl_datetime','<=',$params['end_date'] . ' 23:59:59');
        }

        $total = $query->count();

        $query->select([
            'material_flow.*',
            'material.mate_name as mafl_material_name',
//                'node_account.noac_name as mafl_created_user',
            'receive_user.admin_name as mafl_receive_user',
            'apply_user.admin_name as mafl_apply_user',
        ]);

        $list = $query
            ->offset($point)->limit($pageSize)->get()->toArray();

        $materialId = array_values(array_unique(array_column($list, 'mafl_material_id')));
        $flowId = array_values(array_unique(array_column($list, 'mafl_id')));

        $specificationArr = MaterialSpecificationRelation::query()
            ->leftJoin('material_specification','material_specification.masp_id','=','material_specification_relation.masp_specification_id')
            ->whereIn('masp_material_id',$materialId)
            ->orderBy('material_specification.masp_sort','desc')
            ->orderBy('material_specification.masp_id','desc')
            ->select([
                'masp_material_id',
                'material_specification.masp_name'
            ])->get()->groupBy('masp_material_id')->toArray();

        $consumeCountArr = MaterialFlowConsume::query()->whereIn('mafl_out_id',$flowId)
            ->select([
                'mafl_out_id',
                DB::raw("sum(mafl_number) as number"),
            ])
            ->groupBy(['mafl_out_id'])->pluck('number','mafl_out_id')->toArray();

        foreach ($list as $key => &$value){
            $value['consume_status'] = 1;
            $value['mafl_specification_name'] = array_column($specificationArr[$value['mafl_material_id']],'masp_name') ?? [];
            $value['consume_number'] = $consumeCountArr[$value['mafl_id']] ?? 0;
        }

        unset($value);

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    public function addConsumeFlow($params)
    {
        $flowData = MaterialFlow::query()->where(['mafl_id' => $params['flow_id'],'mafl_type' => 2])->first();
        if(!$flowData){
            ResponseLogic::setMsg('流水记录不存在');
            return false;
        }

        $flowData = $flowData->toArray();

        $hasConsumeNumber = MaterialFlowConsume::query()->where(['mafl_out_id' => $params['flow_id']])->sum('mafl_number');

        if($hasConsumeNumber + $params['number'] > $flowData['mafl_number']){
            ResponseLogic::setMsg('消耗数量已超出该出库总数量');
            return false;
        }

        $insertData = [
            'mafl_out_id' => $params['flow_id'],
            'mafl_number' => $params['number'],
            'mafl_date' => $params['date'],
            'mafl_admin_id' => $params['admin_id'],
            'mafl_remark' => $params['remark'] ?? '',
            'mafl_operator_id' => AuthLogic::$userId,
        ];

        if(MaterialFlowConsume::query()->insert($insertData) === false){
            ResponseLogic::setMsg('插入消耗几率失败');
            return false;
        }

        return [];
    }

    public function getConsumeList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $flowData = MaterialFlow::query()->where(['mafl_id' => $params['flow_id'],'mafl_type' => 2])->first();
        if(!$flowData){
            ResponseLogic::setMsg('流水记录不存在');
            return false;
        }

        $list = MaterialFlowConsume::query()
            ->leftJoin('admin','admin_id','=','mafl_admin_id')
            ->where(['mafl_out_id' => $params['flow_id']])
            ->orderBy('mafl_id','desc')
            ->select([
                'mafl_id',
                'mafl_number',
                'mafl_date',
                'admin_name'
            ])
            ->offset($point)->limit($pageSize)->get()->toArray();

        return $list;
    }
}
