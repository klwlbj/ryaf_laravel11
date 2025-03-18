<?php

namespace App\Http\Logic;

use App\Models\File;
use App\Models\Material;
use App\Models\MaterialApply;
use App\Models\MaterialApplyDetail;
use App\Models\MaterialDetail;
use App\Models\MaterialFlow;
use App\Models\MaterialInventory;
use App\Models\MaterialSpecificationRelation;
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
            ->leftJoin('admin as verify_user','material_flow.mafl_verify_user_id','=','verify_user.admin_id')
            ->leftJoin('warehouse','warehouse.waho_id','=','material_flow.mafl_warehouse_id')
        ;

        if(in_array(AuthLogic::$userId,MaterialLogic::$onlyAccessory)){
            $query->where(['material.mate_category_id' => 2]);
        }

        if(isset($params['material_id']) && $params['material_id']){
            $query->where(['material_flow.mafl_material_id' => $params['material_id']]);
        }

        if(isset($params['type']) && $params['type']){
            $query->where(['material_flow.mafl_type' => $params['type']]);
        }

        if(isset($params['status']) && $params['status']){
            $query->where(['material_flow.mafl_status' => $params['status']]);
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
                'verify_user.admin_name as mafl_verify_user',
                'warehouse.waho_name as mafl_warehouse_name',
            ]);

        if(!empty($params['order_by_status'])){
            $query->orderBy('material_flow.mafl_status','asc');
        }

        $query->orderBy('material_flow.mafl_datetime','desc');

        if(!empty($params['is_all'])){
            $list = $query
                ->get()->toArray();
        }else{
            $list = $query
                ->offset($point)->limit($pageSize)->get()->toArray();
        }


        $ids = array_column($list, 'mafl_id');
        $materialId = array_values(array_unique(array_column($list, 'mafl_material_id')));

        $fileList = File::query()
            ->whereIn('file_relation_id', $ids)->where(['file_type' => 'material_flow'])
            ->select(['file_relation_id','file_name','file_path'])->get()->groupBy('file_relation_id')->toArray();

        $specificationArr = MaterialSpecificationRelation::query()
            ->leftJoin('material_specification','material_specification.masp_id','=','material_specification_relation.masp_specification_id')
            ->whereIn('masp_material_id',$materialId)
            ->orderBy('material_specification.masp_sort','desc')
            ->orderBy('material_specification.masp_id','desc')
            ->select([
                'masp_material_id',
                'material_specification.masp_name'
            ])->get()->groupBy('masp_material_id')->toArray();

        $lastFlowArr = MaterialFlow::getLastFlow($materialId);

        foreach ($list as $key => &$value) {
            $value['is_last'] = ($value['mafl_id'] == $lastFlowArr[$value['mafl_material_id']]) ? true : false;
            $value['file_list'] = $fileList[$value['mafl_id']] ?? '';
            $value['mafl_price'] = bcdiv($value['mafl_price_tax'],1 + $value['mafl_tax']/100,2);
            $value['mafl_invoice_type_msg'] = MaterialFlow::$invoiceTypeArr[$value['mafl_invoice_type']] ?? '未确认';
            if(isset($specificationArr[$value['mafl_material_id']])){
                $value['mafl_specification_name'] = array_column($specificationArr[$value['mafl_material_id']],'masp_name');
            }else{
                $value['mafl_specification_name'] = [];
            }
        }

        unset($value);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    public function inComing($params)
    {
        ToolsLogic::writeLog('入库操作','material_flow',$params);
        $materialData = Material::getDataById($params['material_id']);

        if(!$materialData){
            ResponseLogic::setMsg('物品数据不存在');
            return false;
        }

        # 获取默认单价
        $defaultPriceTax = $params['price_tax'] ?? ($materialData['mate_price_tax'] ?? 0);
        $defaultTax = $params['tax'] ?? ($materialData['mate_tax'] ?? 0);
        $defaultInvoiceType = $params['invoice_type'] ?? ($materialData['mate_invoice_type'] ?? 0);

        $incomingData = [
            'mafl_material_id'  => $params['material_id'],
            'mafl_warehouse_id'  => $params['warehouse_id'],
            'mafl_type' => 1,
            'mafl_number' => $params['number'],
            'mafl_tax' => $defaultTax,
            'mafl_price_tax' => $defaultPriceTax,
            'mafl_invoice_type' => $defaultInvoiceType,
            'mafl_verify_user_id' => $params['verify_user_id'],
            'mafl_production_date' => $params['production_date'],
            'mafl_expire_date' => $params['expire_date'],
            'mafl_datetime' => $params['datetime'],
            'mafl_remark' => $params['remark'] ?? '',
            'mafl_status' => 1,
            'mafl_operator_id' => AuthLogic::$userId
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
        ToolsLogic::writeLog('出库操作','material_flow',$params);
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
            'mafl_price_tax' => 0,
            'mafl_tax' => 0,
            'mafl_invoice_type' => 0,
            'mafl_verify_user_id' => $params['verify_user_id'],
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

        if(!empty($fileInsertData)){
            if(File::query()->insert($fileInsertData) === false){
                DB::rollBack();
                ResponseLogic::setMsg('插入附件失败');
                return false;
            }
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
                ->orderBy('made_datetime','asc')
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

        #如果选择了申购单
        if(!empty($params['apply_id'])){
            $applyDetailData = MaterialApplyDetail::query()->where(['maap_id' => $params['apply_id']])->first();
            if(!$applyDetailData){
                DB::rollBack();
                ResponseLogic::setMsg('申购单不存在或已出库');
                return false;
            }

            if(MaterialApplyDetail::query()->where(['maap_id' => $params['apply_id']])->update(['maap_flow_id' => $flowId,'maap_status' => 2]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('更新申购单详情表失败');
                return false;
            }

            #判断是否已完成申购单  如果完成把主申购单变成完成  如果未完成则变成出库中
            if(!MaterialApplyDetail::query()->where(['maap_apply_id' => $applyDetailData->maap_apply_id,'maap_status' => 1])->exists()){
                if(MaterialApply::query()->where(['maap_id' => $applyDetailData->maap_apply_id])->update(['maap_status' => 4]) === false){
                    DB::rollBack();
                    ResponseLogic::setMsg('更新申购单状态失败');
                    return false;
                }
            }else{
                if(MaterialApply::query()->where(['maap_id' => $applyDetailData->maap_apply_id])->update(['maap_status' => 3]) === false){
                    DB::rollBack();
                    ResponseLogic::setMsg('更新申购单状态失败');
                    return false;
                }
            }
        }

        DB::commit();

        Material::delCacheById($params['material_id']);

        return [];
    }

    public function getInfo($params)
    {
        $data = MaterialFlow::query()
            ->leftJoin('material','material.mate_id','=','material_flow.mafl_material_id')
            ->select([
                'material_flow.*',
                'material.mate_name as mafl_material_name'
            ])
            ->where(['mafl_id' => $params['id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        return $data->toArray();
    }

    public function inComingUpdate($params)
    {
        ToolsLogic::writeLog('更新入库信息','material_flow',$params);
        $data = MaterialFlow::query()->where(['mafl_id' => $params['id'],'mafl_type' => 1])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        $update = [

        ];

        $detailUpdate = [

        ];

        if(!empty($params['production_date'])){
            $update['mafl_production_date'] = $params['production_date'];
            $detailUpdate['made_production_date'] = $params['production_date'];
        }

        if(!empty($params['expire_date'])){
            $update['mafl_expire_date'] = $params['expire_date'];
            $detailUpdate['made_expire_date'] = $params['expire_date'];
        }

        if(!empty($params['remark'])){
            $update['mafl_remark'] = $params['remark'];
        }

        if(!empty($update)){
            if(MaterialFlow::query()->where(['mafl_id' => $params['id']])->update($update) === false){
                ResponseLogic::setMsg('更新入库记录失败');
                return false;
            }
        }

        if(!empty($detailUpdate)){
            if(MaterialDetail::query()->where(['made_in_id' => $params['id']])->update($detailUpdate) === false){
                ResponseLogic::setMsg('更新入库物品记录失败');
                return false;
            }
        }

        return [];
    }

    public function verify($params)
    {
        ToolsLogic::writeLog('最终确认流水','material_flow',$params);
        $data = MaterialFlow::query()->where(['mafl_id' => $params['id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        $data = $data->toArray();

        if($data['mafl_status'] != 1){
            ResponseLogic::setMsg('记录不为待确认状态');
            return false;
        }

        if($data['mafl_verify_user_id'] != AuthLogic::$userId){
            ResponseLogic::setMsg('没有权限操作');
            return false;
        }

        if(MaterialFlow::query()->where(['mafl_id' => $params['id']])->update(['mafl_status' => 2]) === false){
            ResponseLogic::setMsg('确认失败');
            return false;
        }

        return [];
    }

    public function setPrice($params)
    {
        $data = MaterialFlow::query()->where(['mafl_id' => $params['id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        if(MaterialFlow::query()->where(['mafl_id' => $params['id']])->update([
                'mafl_price_tax' => $params['price_tax'],
                'mafl_tax' => $params['tax'],
                'mafl_invoice_type' => $params['invoice_type']
            ]) === false){
            ResponseLogic::setMsg('设置价格失败');
            return false;
        }

        return [];
    }

    public function cancel($params)
    {
        ToolsLogic::writeLog('撤销出入库记录','material_flow',$params);
        $flowData = MaterialFlow::query()->where(['mafl_id' => $params['id']])->first();

        if(!$flowData){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        $flowData = $flowData->toArray();

        #获取最后一次操作记录id
        $lastFlowId = MaterialFlow::query()->where(['mafl_material_id' => $flowData['mafl_material_id']])
            ->select(['mafl_id'])->orderBy('mafl_id','desc')->limit(1)->value('mafl_id') ?: 0;

        if($params['id'] != $lastFlowId){
            ResponseLogic::setMsg('该记录不为最新记录，不能撤销');
            return false;
        }


        if($flowData['mafl_type'] == 1){
            #撤回入库
            DB::beginTransaction();
            #删除明细表
            if(MaterialDetail::query()->where(['made_in_id' => $params['id']])->delete() === false){
                DB::rollBack();
                ResponseLogic::setMsg('删除明细表失败');
                return false;
            }

            #删除流水记录
            if(MaterialFlow::query()->where(['mafl_id' => $params['id']])->delete() === false){
                DB::rollBack();
                ResponseLogic::setMsg('删除流水记录失败');
                return false;
            }

            #修改仓库表库存数据
            if(MaterialInventory::query()
                    ->where(['main_warehouse_id' => $flowData['mafl_warehouse_id'],'main_material_id' => $flowData['mafl_material_id']])
                    ->update(['main_number' => DB::raw("main_number-".$flowData['mafl_number'])]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('修改仓库记录表失败');
                return false;
            }

            #修改物品表库存数据
            if(Material::query()
                    ->where(['mate_id' => $flowData['mafl_material_id']])
                    ->update(['mate_number' => DB::raw("mate_number-".$flowData['mafl_number'])]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('修改物品表库存失败');
                return false;
            }

            DB::commit();
        }else{
            #撤回出库
            DB::beginTransaction();

            #把明细表的出库字段还原
            if(MaterialDetail::query()->where(['made_out_id' => $params['id']])->update(['made_receive_user_id' => null,'made_status' => 1,'made_out_id' => null]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('明细表的出库字段还原失败');
                return false;
            }

            #删除流水记录
            if(MaterialFlow::query()->where(['mafl_id' => $params['id']])->delete() === false){
                DB::rollBack();
                ResponseLogic::setMsg('删除流水记录失败');
                return false;
            }

            #修改仓库表库存数据
            if(MaterialInventory::query()
                    ->where(['main_warehouse_id' => $flowData['mafl_warehouse_id'],'main_material_id' => $flowData['mafl_material_id']])
                    ->update(['main_number' => DB::raw("main_number+".$flowData['mafl_number'])]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('修改仓库记录表失败');
                return false;
            }

            #修改物品表库存数据
            if(Material::query()
                    ->where(['mate_id' => $flowData['mafl_material_id']])
                    ->update(['mate_number' => DB::raw("mate_number+".$flowData['mafl_number'])]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('修改物品表库存失败');
                return false;
            }

            DB::commit();
        }

        return [];
    }
}
