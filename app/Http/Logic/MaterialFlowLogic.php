<?php

namespace App\Http\Logic;

use App\Models\File;
use App\Models\Material;
use App\Models\MaterialDetail;
use App\Models\MaterialFlow;
use App\Models\MaterialInventory;
use Illuminate\Support\Facades\DB;

class MaterialFlowLogic extends BaseLogic
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
            ->leftJoin('warehouse','warehouse.waho_id','=','material_flow.mafl_warehouse_id')
        ;

        if(isset($params['material_id']) && $params['material_id']){
            $query->where(['material_flow.mafl_material_id' => $params['material_id']]);
        }

        if(isset($params['type']) && $params['type']){
            $query->where(['material_flow.mafl_type' => $params['type']]);
        }

        $total = $query->count();

        $list = $query
            ->select([
                'material_flow.*',
                'material.mate_name as mafl_material_name',
//                'node_account.noac_name as mafl_created_user',
                'receive_user.admin_name as mafl_receive_user',
                'apply_user.admin_name as mafl_apply_user',
                'warehouse.waho_name as mafl_warehouse_name',
            ])
            ->orderBy('material_flow.mafl_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        $ids = array_column($list, 'mafl_id');

        $fileList = File::query()
            ->whereIn('file_relation_id', $ids)->where(['file_type' => 'material_flow'])
            ->select(['file_relation_id','file_name','file_path'])->get()->groupBy('file_relation_id')->toArray();

        foreach ($list as $key => &$value) {
            $value['file_list'] = $fileList[$value['mafl_id']] ?? '';
        }

        unset($value);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    public function inComing($params)
    {
        $materialData = Material::getDataById($params['material_id']);

        if(!$materialData){
            ResponseLogic::setMsg('物品数据不存在');
            return false;
        }

        $incomingData = [
            'mafl_material_id'  => $params['material_id'],
            'mafl_warehouse_id'  => $params['warehouse_id'],
            'mafl_type' => 1,
            'mafl_number' => $params['number'],
            'mafl_production_date' => $params['production_date'],
            'mafl_expire_date' => $params['expire_date'],
            'mafl_datetime' => $params['datetime'],
            'mafl_remark' => $params['remark'] ?? '',
            'mafl_operator_id' => AuthLogic::$userId #操作人 默认写死2
        ];

        #查看是否有该仓库
        $inventoryId = MaterialInventory::query()
            ->where(['main_warehouse_id' => $params['warehouse_id'],'main_material_id' => $params['material_id']])
            ->value('main_id');

        DB::beginTransaction();

        #插入库存流水
        $flowId = MaterialFlow::query()->insertGetId($incomingData);
        if(!$flowId){
            DB::rollBack();
            ResponseLogic::setMsg('插入库存流水失败');
            return false;
        }

        if(empty($inventoryId)){
            if(MaterialInventory::query()->insert([
                'main_warehouse_id' => $params['warehouse_id'],
                'main_material_id' => $params['material_id'],
                'main_number' => $params['number'],
            ]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('创建物品仓库库存失败');
                return false;
            }
        }else{
            if(MaterialInventory::query()->where(['main_id' => $inventoryId])->update(['main_number' => DB::raw("main_number+".$params['number'])]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('更新物品仓库库存失败');
                return false;
            }
        }

        #变更物品库存数量
        if(Material::query()->where(['mate_id' => $params['material_id']])->update(['mate_number' => DB::raw("mate_number+".$params['number'])]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新总库存失败');
            return false;
        }

        $detailInsert = [];

        for ($i = 0; $i < $params['number']; $i++) {
            $detailInsert[] = [
                'made_material_id' => $params['material_id'],
                'made_warehouse_id' => $params['warehouse_id'],
                'made_in_id' => $flowId,
                'made_is_deliver' => $materialData['mate_is_deliver'],
                'made_production_date' => $params['production_date'],
                'made_expire_date' => $params['expire_date'],
                'made_datetime' => $params['datetime'],
                'made_status' => 1,
            ];

            if(count($detailInsert) >= 1000){
                MaterialDetail::query()->insert($detailInsert);
                $detailInsert = [];
            }
        }

        if(!empty($detailInsert)){
            MaterialDetail::query()->insert($detailInsert);
            $detailInsert = [];
        }

        DB::commit();

        Material::delCacheById($params['material_id']);

        return ['id' => $flowId];
    }

    public function outComing($params)
    {
        $materialData = Material::query()
            ->where(['mate_id' => $params['material_id']])->select(['mate_id','mate_number'])->first();

        if(!$materialData){
            ResponseLogic::setMsg('物品数据不存在');
            return false;
        }

        #查看物品仓库库存
        $inventoryData = MaterialInventory::query()
            ->where(['main_warehouse_id' => $params['warehouse_id'],'main_material_id' => $params['material_id']])
            ->first();

        if(!$inventoryData){
            ResponseLogic::setMsg('该仓库物品库存不足');
            return false;
        }


        if($inventoryData->main_number < $params['number']){
            ResponseLogic::setMsg('该库存物品不足，当前库存：' . $inventoryData->main_number);
            return false;
        }

        $fileList = ToolsLogic::jsonDecode($params['file_list']);

        $outComingData = [
            'mafl_material_id'  => $params['material_id'],
            'mafl_warehouse_id'  => $params['warehouse_id'],
            'mafl_type' => 2,
            'mafl_number' => $params['number'],
            'mafl_purpose' => $params['purpose'],
            'mafl_apply_user_id' => $params['apply_user_id'],
            'mafl_receive_user_id' => $params['receive_user_id'],
            'mafl_datetime' => $params['datetime'],
            'mafl_remark' => $params['remark'] ?? '',
            'mafl_operator_id' => AuthLogic::$userId #操作人 默认写死2
        ];

        DB::beginTransaction();

        #插入库存流水
        if(($flowId = MaterialFlow::query()->insertGetId($outComingData)) === false){
            DB::rollBack();
            ResponseLogic::setMsg('插入流水记录失败');
            return false;
        }

        $fileInsertData = [];
        if(!empty($fileList)){
            foreach ($fileList as $key => $value){
                $fileInsertData[] = [
                    'file_relation_id' => $flowId,
                    'file_type' => 'material_flow',
                    'file_name' => $value['name'],
                    'file_ext' => $value['ext'],
                    'file_path' => $value['url'],
                ];
            }
        }

        if(File::query()->insert($fileInsertData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('插入附件失败');
            return false;
        }

        #变更该仓库物品流水
        if(MaterialInventory::query()->where(['main_warehouse_id' => $params['warehouse_id'],'main_material_id' => $params['material_id']])->update(['main_number' => DB::raw("main_number-".$params['number'])]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新物品仓库库存失败');
            return false;
        }
        #变更物品库存数量
        if(Material::query()->where(['mate_id' => $params['material_id']])->update([
            'mate_number' => DB::raw("mate_number-".$params['number']),
        ]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新物品仓库库存失败');
            return false;
        }
        #把物品变更成出库状态
        if(MaterialDetail::query()
            ->where(['made_material_id' => $params['material_id'],'made_status' => 1])
            ->orderBy('made_id','asc')
            ->limit($params['number'])
            ->update([
                'made_out_id' => $flowId,
                'made_status' => 2,
                'made_receive_user_id' => $params['receive_user_id']
            ]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新物品详情失败');
            return false;
        }


        DB::commit();

        Material::delCacheById($params['material_id']);

        return [];
    }
}
