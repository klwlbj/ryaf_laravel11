<?php

namespace App\Http\Logic;

use App\Models\Admin;
use App\Models\Approval;
use App\Models\ApprovalProcess;
use App\Models\ApprovalProcessModel;
use App\Models\ApprovalRelation;
use App\Models\File;
use App\Models\Material;
use App\Models\MaterialApply;
use App\Models\MaterialApplyDetail;
use App\Models\MaterialDetail;
use App\Models\MaterialFlow;
use App\Models\MaterialPurchase;
use App\Models\MaterialPurchaseDetail;
use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\select;

class MaterialApplyLogic extends BaseLogic
{
    public static $materialPriceArr = [
        '2' => 240,
        '3' => 240,
        '65' => 240,
        '64' => 240,
    ];

    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $departmentId = Admin::query()->where(['admin_id' => AuthLogic::$userId])->value('admin_department_id') ?: 0;

        $query = MaterialApply::query()
            ->leftJoin('admin','admin.admin_id','=','material_apply.maap_admin_id');

        if(in_array($departmentId,[19,20])){
            $query->where('admin.admin_department_id','=',$departmentId);
        }

//        $query->where(['maap_admin_id' => AuthLogic::$userId]);

        if (isset($params['start_date']) && $params['start_date']) {
            $query->where('maap_crt_time', '>=', $params['start_date']);
        }

        if (isset($params['end_date']) && $params['end_date']) {
            $query->where('maap_crt_time', '<=', $params['end_date']);
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where(['maap_status' => $params['status']]);
        }

        if (isset($params['material_id']) && $params['material_id']) {
            $ids = MaterialApplyDetail::query()->where(['maap_material_id' => $params['material_id']])->pluck('maap_apply_id')->toArray();
            $query->whereIn('maap_id', $ids);
        }

        $total = $query->count();

        $list = $query
            ->select([
                'material_apply.*',
                'admin.admin_name'
            ])
            ->orderBy('maap_id', 'desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        $ids = array_column($list, 'maap_id');

        $approvalIds = Approval::query()
            ->where(['appr_type' => 1])
            ->whereIn('appr_relation_id',$ids)
            ->select([
                DB::raw("SUBSTRING_INDEX(GROUP_CONCAT(appr_id ORDER BY appr_id DESC), ',', 1) AS last_appr_id"),
            ])->groupBy(['appr_relation_id'])->pluck('last_appr_id')->toArray();

        $approvalDataArr = Approval::query()
            ->whereIn('appr_id',$approvalIds)
            ->select([
                'appr_id',
                'appr_relation_id',
                'appr_name',
                'appr_reason'
            ])->get()->keyBy('appr_relation_id')->toArray();

        $detailArr = MaterialApplyDetail::query()
            ->leftJoin('material', 'material.mate_id', '=', 'material_apply_detail.maap_material_id')
            ->whereIn('maap_apply_id', $ids)
            ->select([
                'material_apply_detail.maap_id',
                'material_apply_detail.maap_apply_id',
                'material.mate_name',
                'material.mate_unit',
                'material_apply_detail.maap_number',
            ])
            ->get()->groupBy('maap_apply_id')->toArray();

        foreach ($list as $key => &$value) {
            $value['is_update'] = (in_array($value['maap_status'],[0,5]) && $value['maap_admin_id'] == AuthLogic::$userId);
            $value['is_cancel'] = (in_array($value['maap_status'],[1]) && $value['maap_admin_id'] == AuthLogic::$userId);
//            $value['is_handle'] = (in_array($value['maap_status'],[2,3]) && AuthLogic::$userId == 10010);
            $value['is_handle'] = false;
            if (isset($detailArr[$value['maap_id']])) {
                $value['detail'] = $detailArr[$value['maap_id']];
            } else {
                $value['detail'] = [];
            }

            $value['appr_name'] = $approvalDataArr[$value['maap_id']]['appr_name'] ?? '';
            $value['appr_id'] = $approvalDataArr[$value['maap_id']]['appr_id'] ?? '';
            $value['appr_reason'] = $approvalDataArr[$value['maap_id']]['appr_reason'] ?? '';
        }

        unset($value);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    public function getSelectList($params)
    {
        $query = MaterialApply::query()
            ->leftJoin('material_apply_detail','material_apply_detail.maap_apply_id','=','material_apply.maap_id')
            ->leftJoin('material','material.mate_id','=','material_apply_detail.maap_material_id')
            ->leftJoin('admin','admin.admin_id','=','material_apply.maap_admin_id')
            ->whereIn('material_apply.maap_status',[2,3])
            ->where(['material_apply_detail.maap_status' => 1])
            ->select([
                'material_apply_detail.maap_apply_id',
                'material_apply_detail.maap_id',
                'material_apply_detail.maap_material_id',
                'material_apply.maap_purpose',
                'admin.admin_id',
                'admin.admin_name',
                'material.mate_name',
                'material_apply_detail.maap_number',
            ]);

        if(!empty($params['material_id'])){
            $query->where(['material_apply_detail.maap_apply_id' => $params['material_id']]);
        }

        $list = $query->get()->toArray();

        $applyIds = array_column($list,'maap_apply_id');

        $fileGroup = File::query()->whereIn('file_relation_id',$applyIds)
            ->where(['file_type' => 'material_apply'])
            ->select([
                'file_relation_id',
                'file_name',
                'file_ext',
                'file_path'
            ])->get()->groupBy('file_relation_id')->toArray();

        foreach ($list as $key => &$value){
            $value['file_list'] = $fileGroup[$value['maap_apply_id']] ?? [];
        }

        unset($value);

        return $list;
    }

    public function getRelationList($params)
    {
        $query = $this->getRelationQuery($params);

        return $query->groupBy(['appr_id'])->select([
            'appr_name as name',
            'appr_id as id',
            'appr_sn as sn',
            'material_apply.maap_crt_time as date'
        ])->orderBy('appr_id','desc')->limit(100)->get()->toArray();
    }

    public function getRelationQuery($params)
    {
        $query = MaterialApply::query()
            ->leftJoin('approval','appr_relation_id','=',DB::raw("material_apply.maap_id and appr_type=1"))
            ->leftJoin('material_apply_detail','material_apply_detail.maap_apply_id','=','material_apply.maap_id')
            ->whereIn('appr_status',[2])
            ->where(['appr_admin_id' => AuthLogic::$userId]);

        #如果存在物品id
        if(!empty($params['material_ids'])){
            $ids = explode(',',$params['material_ids']);
            $query->whereIn('maap_material_id',$ids);
        }

        return $query;
    }

    public function getApprovalInfo($id)
    {
        $data = MaterialApply::query()
            ->where(['maap_id' => $id])->first();
        if(!$data){
            return [];
        }
        $data = $data->toArray();

        #详情数据
        $detail = MaterialApplyDetail::query()
            ->leftJoin('material','mate_id','=','maap_material_id')
            ->where(['maap_apply_id' => $id])
            ->select(['maap_material_id','maap_number','maap_total_price','mate_name','mate_unit'])
            ->get()->toArray();
        $data['detail'] = $detail;

        $data['file_list'] = File::query()
            ->where(['file_type' => 'material_apply','file_relation_id' => $id])
            ->select(['file_relation_id','file_name','file_path','file_ext'])->get()->toArray();

        return $data;
    }

    public function getInfo($params)
    {
        $data = MaterialApply::query()
            ->where(['maap_id' => $params['id']])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }
        $data = $data->toArray();

        #详情数据
        $detail = MaterialApplyDetail::query()
            ->leftJoin('material','mate_id','=','maap_material_id')
            ->where(['maap_apply_id' => $params['id']])
            ->select(['material_apply_detail.*','mate_name','mate_unit'])
            ->get()->toArray();
        $data['detail'] = $detail;

        $data['file_list'] = File::query()
            ->where(['file_type' => 'material_apply','file_relation_id' => $params['id']])
            ->select(['file_relation_id','file_name','file_path','file_ext'])->get()->toArray();

        #审批数据
        if(isset($params['approval_id']) && !empty($params['approval_id'])){
            $approvalData = Approval::query()->where(['appr_id' => $params['approval_id']])->orderBy('appr_id','desc')->first();
        }else{
            $approvalData = Approval::query()->where(['appr_type' => 1,'appr_relation_id' => $params['id']])->orderBy('appr_id','desc')->first();
        }

        $data['approval'] = ($approvalData) ? $approvalData->toArray() : [];

        #审批流程数据
        if(!empty($data['approval'])){
            $data['approval_process'] = ApprovalProcess::query()
                ->leftJoin('admin','admin_id','=','appr_admin_id')
                ->where(['appr_approval_id' => $data['approval']['appr_id']])
                ->select(['approval_process.*','admin_name'])
                ->orderBy('appr_index','asc')->get()->toArray();
            #判断是否可审批
            $approvalAdminId = ApprovalProcess::query()
                ->where(['appr_approval_id' => $data['approval']['appr_id']])
                ->value('appr_admin_id') ?: null;
        }else{
            $data['approval_process'] = [];
            $approvalAdminId = null;
        }

        #关联审批单
        $relationIds = ApprovalRelation::query()->where(['apre_approval_id' => $data['approval']['appr_id']])->select(['apre_relation_id'])->pluck('apre_relation_id')->toArray();
        if(!empty($data['approval']) && !empty($relationIds)){
            $data['relation_approval'] = Approval::query()
                ->whereIn('appr_id',$relationIds)
                ->get()->toArray();
        }else{
            $data['relation_approval'] = [];
        }

        $data['is_approval'] = $approvalAdminId == AuthLogic::$userId;
        $data['relation_id'] = $relationIds;
        return $data;
    }

    public function getPreInfo($params)
    {
        $detail = ToolsLogic::jsonDecode($params['detail']);

//        print_r($detail);die;

        $materialIds = array_column($detail,'id');
        #判断申领物品详情是否符合
        $materialArr = Material::query()->whereIn('mate_id',$materialIds)
            ->select(['mate_id','mate_category_id','mate_number','mate_name'])->get()->keyBy('mate_id')->toArray();

        #查询已锁库存数量
        $lockMaterialNumberArr = MaterialApplyDetail::query()
            ->leftJoin('material_apply','material_apply.maap_id','=','material_apply_detail.maap_apply_id')
            ->where(['material_apply_detail.maap_status' => 1])
            ->whereIn('material_apply.maap_status',$materialIds)
            ->whereIn('material_apply_detail.maap_material_id',[1,2,3])
            ->select([
                'material_apply_detail.maap_material_id',
                DB::raw("sum(material_apply_detail.maap_number) as count"),
            ])->groupBy(['material_apply_detail.maap_material_id'])->pluck('count','maap_material_id')->toArray();

        $applyTotalPrice = 0;

        foreach ($detail as $key => &$value){
            if(!isset($value['id'])){
                continue;
            }
            $materialInfo = $materialArr[$value['id']] ?? [];
            if(empty($materialInfo)){
                return [
                    'total_price' => 0,
                    'process_list' => 0,
                    'error_msg' => '物品：' . $value['name'] . '不存在',
                    'error' => true,
                ];
            }

            if($materialInfo['mate_category_id'] != $params['category_id']){
                return [
                    'total_price' => 0,
                    'process_list' => 0,
                    'error_msg' => '物品：' . $value['name'] . '类型不为所选类型',
                    'error' => true,
                ];
            }

            if(!isset($value['number']) || empty($value['number'])){
                return [
                    'total_price' => 0,
                    'process_list' => 0,
                    'error_msg' => '物品：' . $value['name'] . '需要填写数量',
                    'error' => true,
                ];
            }

            $lockCount = $lockMaterialNumberArr[$value['id']] ?? 0;
            $realRemainNumber = $materialInfo['mate_number'] - $lockCount;
            if($realRemainNumber < $value['number']){
                return [
                    'total_price' => 0,
                    'process_list' => 0,
                    'error_msg' => '物品：' . $value['name'] . '库存不足。剩余库存数量：' . $realRemainNumber,
                    'error' => true,
                ];
            }

            #计算金额
            $totalPrice = $this->getTotalPrice($value['id'],$lockCount,$value['number']);
            $value['total_price'] = $totalPrice;
            $applyTotalPrice = bcadd($applyTotalPrice,$totalPrice,2);
        }

        unset($value);

        $materialNameArr = array_column($detail,'name');

        $processList = ApprovalProcessModel::getProcessList([
            'total_price' => $applyTotalPrice,
            'purpose' => $params['purpose'],
            'material_name' => $materialNameArr,
        ]);

        $adminIds = array_column($processList,'admin_id');

        $adminArr = Admin::query()->whereIn('admin_id',$adminIds)
            ->select(['admin_id','admin_name'])
            ->pluck('admin_name','admin_id')
            ->toArray();

        if(empty($processList)){
            return [
                'total_price' => 0,
                'process_list' => 0,
                'error_msg' => '审批流程不存在',
                'error' => true,
            ];
        }

        foreach ($processList as $key => &$value){
            $value['admin_name'] = $adminArr[$value['admin_id']] ?? '';
        }

        unset($value);

        return [
            'total_price' => $applyTotalPrice,
            'process_list' => $processList,
            'error_msg' => '',
            'error' => false,
        ];
    }

    public function getTotalPrice($materialId,$lockCount,$number)
    {
        if(isset(self::$materialPriceArr[$materialId])){
            return bcmul(self::$materialPriceArr[$materialId],$number,2);
        }

        $totalPriceArr = MaterialDetail::query()
            ->leftJoin('material_flow','mafl_id','=','made_in_id')
            ->where(['made_material_id' => $materialId,'made_status' => 1])
            ->orderBy('made_datetime','asc')
            ->orderBy('made_id','asc')
            ->offset($lockCount)->limit($number)
            ->select(['mafl_price_tax'])->pluck('mafl_price_tax')->toArray();

        return array_sum($totalPriceArr);
    }

    public function add($params)
    {
        $detail = ToolsLogic::jsonDecode($params['detail']);
        $fileList = ToolsLogic::jsonDecode($params['file_list']);

        if (empty($detail)) {
            ResponseLogic::setMsg('申报详情格式有误');
            return false;
        }
        $materialIds = array_column($detail,'id');

        #查询是否存在历史申领单
        if($this->getRelationQuery(['material_ids' => implode(',',$materialIds)])->exists()){
            if(empty($params['relation_id'])){
                ResponseLogic::setMsg('存在历史申购单,则需要选择关联申购单');
                return false;
            }
        }
//        print_r($detail);die;


        #判断申领物品详情是否符合
        $materialArr = Material::query()->whereIn('mate_id',$materialIds)->select(['mate_id','mate_category_id','mate_number','mate_name'])->get()->keyBy('mate_id')->toArray();

        #查询已锁库存数量
        $lockMaterialNumberArr = MaterialApplyDetail::query()
            ->leftJoin('material_apply','material_apply.maap_id','=','material_apply_detail.maap_apply_id')
            ->where(['material_apply_detail.maap_status' => 1])
            ->whereIn('material_apply.maap_status',$materialIds)
            ->whereIn('material_apply_detail.maap_material_id',[1,2,3])
            ->select([
                'material_apply_detail.maap_material_id',
                DB::raw("sum(material_apply_detail.maap_number) as count"),
            ])->groupBy(['material_apply_detail.maap_material_id'])->pluck('count','maap_material_id')->toArray();

        $applyTotalPrice = 0;

        foreach ($detail as $key => &$value){
            $materialInfo = $materialArr[$value['id']] ?? [];
            if(empty($materialInfo)){
                ResponseLogic::setMsg('物品：' . $value['name'] . '不存在');
                return false;
            }


            if($materialInfo['mate_category_id'] != $params['category_id']){
                ResponseLogic::setMsg('物品：' . $value['name'] . '类型不为所选类型');
                return false;
            }

            if(!isset($value['number']) || empty($value['number'])){
                ResponseLogic::setMsg('物品：' . $value['name'] . '需要填写数量');
                return false;
            }

            $lockCount = $lockMaterialNumberArr[$value['id']] ?? 0;
            $realRemainNumber = $materialInfo['mate_number'] - $lockCount;
            if($realRemainNumber < $value['number']){
                ResponseLogic::setMsg('物品：' . $value['name'] . '库存不足。剩余库存数量：' . $realRemainNumber);
                return false;
            }

            #计算金额
            $totalPrice = $this->getTotalPrice($value['id'],$lockCount,$value['number']);
            $value['total_price'] = $totalPrice;
            $applyTotalPrice = bcadd($applyTotalPrice,$totalPrice,2);
        }

        unset($value);

        $materialNameArr = array_column($detail,'name');

        $processList = ApprovalProcessModel::getProcessList([
            'total_price' => $applyTotalPrice,
            'purpose' => $params['purpose'],
            'material_name' => $materialNameArr,
        ]);

        if(empty($processList)){
            ResponseLogic::setMsg('审批流程不存在');
            return false;
        }

        #申领主表数据
        $applyInsertData = [
            'maap_category_id' => $params['category_id'],
            'maap_admin_id' => AuthLogic::$userId,
            'maap_status' => 1,
            'maap_total_price' => $applyTotalPrice,
            'maap_purpose' => $params['purpose'],
            'maap_remark' => $params['remark'] ?? '',
        ];

        DB::beginTransaction();

        $applyId = MaterialApply::query()->insertGetId($applyInsertData);
        if($applyId === false){
            DB::rollBack();
            ResponseLogic::setMsg('插入申领表失败');
            return false;
        }

        #申领详情表
        $applyDetailInsertData = [];
        foreach ($detail as $key => $value){
            $applyDetailInsertData[] = [
                'maap_apply_id' => $applyId,
                'maap_material_id' => $value['id'],
                'maap_number' => $value['number'],
                'maap_total_price' => $value['total_price'],
                'maap_status' => 1,
            ];
        }


        if(!empty($fileList)){
            $fileInsertData = [];
            foreach ($fileList as $key => $value){
                $fileInsertData[] = [
                    'file_relation_id' => $applyId,
                    'file_type' => 'material_apply',
                    'file_name' => $value['name'],
                    'file_ext' => $value['ext'],
                    'file_path' => $value['url'],
                ];
            }

            if(File::query()->insert($fileInsertData) === false){
                DB::rollBack();
                ResponseLogic::setMsg('插入附件失败');
                return false;
            }
        }

        if(MaterialApplyDetail::query()->insert($applyDetailInsertData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('插入申领详情表失败');
            return false;
        }

        #审批主表数据
        $approvalInsertData = [
            'appr_sn' => Approval::getSn(),
            'appr_name' => $params['name'],
            'appr_reason' => $params['reason'],
            'appr_admin_id' => AuthLogic::$userId,
            'appr_relation_id' => $applyId,
            'appr_type' => 1,
            'appr_status' => 1,
//            'appr_relation_approval_id' => $params['relation_id'] ?? 0
        ];

        $approvalId = Approval::query()->insertGetId($approvalInsertData);

        if($approvalId === false){
            DB::rollBack();
            ResponseLogic::setMsg('插入审批表失败');
            return false;
        }

        #关联审批单
        if(!empty($params['relation_id'])){
            $relationInsert = [];
            $relationIds = explode(',',$params['relation_id']);
            foreach ($relationIds as $key => $value){
                $relationInsert[] = [
                    'apre_approval_id' => $approvalId,
                    'apre_relation_id' => $value
                ];
            }

            if(ApprovalRelation::query()->insert($relationInsert) === false){
                DB::rollBack();
                ResponseLogic::setMsg('插入关联审批信息失败');
                return false;
            }
        }

        $approvalProcessInsertData = [];
        #审批流程表数据
        foreach ($processList as $key => $value){
            $approvalProcessInsertData[] = [
                'appr_approval_id' => $approvalId,
                'appr_admin_id' => $value['admin_id'],
                'appr_type' => $value['type'],
                'appr_index' => $value['index'],
                'appr_status' => 1,
            ];
        }

        if(ApprovalProcess::query()->insert($approvalProcessInsertData) === false){
            DB::rollBack();
            ResponseLogic::setMsg('插入审批流程表失败');
            return false;
        }

        #进入第下一个流程
        if(!ApprovalProcess::setNextProcess($approvalId)){
            DB::rollBack();
            return false;
        }

        DB::commit();
        return ['id' => $applyId];
    }

    public function update($params)
    {
        $detail = ToolsLogic::jsonDecode($params['detail']);
        $fileList = ToolsLogic::jsonDecode($params['file_list']);

        if (empty($detail)) {
            ResponseLogic::setMsg('申报详情格式有误');
            return false;
        }

        $applyData = MaterialApply::query()->where(['maap_id' => $params['id']])->first();
        if(!$applyData){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $materialIds = array_column($detail,'id');
        #查询是否存在历史申领单
        if($this->getRelationQuery(['material_ids' => implode(',',$materialIds)])->exists()){
            if(empty($params['relation_id'])){
                ResponseLogic::setMsg('存在历史申购单,则需要选择关联申购单');
                return false;
            }
        }
//        print_r($detail);die;


        #判断申领物品详情是否符合
        $materialArr = Material::query()->whereIn('mate_id',$materialIds)->select(['mate_id','mate_category_id','mate_number','mate_name'])->get()->keyBy('mate_id')->toArray();

        #查询已锁库存数量
        $lockMaterialNumberArr = MaterialApplyDetail::query()
            ->leftJoin('material_apply','material_apply.maap_id','=','material_apply_detail.maap_apply_id')
            ->where(['material_apply_detail.maap_status' => 1])
            ->whereIn('material_apply.maap_status',$materialIds)
            ->whereIn('material_apply_detail.maap_material_id',[1,2,3])
            ->select([
                'material_apply_detail.maap_material_id',
                DB::raw("sum(material_apply_detail.maap_number) as count"),
            ])->groupBy(['material_apply_detail.maap_material_id'])->pluck('count','maap_material_id')->toArray();

        $applyTotalPrice = 0;

        foreach ($detail as $key => &$value){
            $materialInfo = $materialArr[$value['id']] ?? [];
            if(empty($materialInfo)){
                ResponseLogic::setMsg('物品：' . $value['name'] . '不存在');
                return false;
            }

            if($materialInfo['mate_category_id'] != $params['category_id']){
                ResponseLogic::setMsg('物品：' . $value['name'] . '类型不为所选类型');
                return false;
            }

            if(!isset($value['number']) || empty($value['number'])){
                ResponseLogic::setMsg('物品：' . $value['name'] . '需要填写数量');
                return false;
            }

            $lockCount = $lockMaterialNumberArr[$value['id']] ?? 0;
            $realRemainNumber = $materialInfo['mate_number'] - $lockCount;
            if($realRemainNumber < $value['number']){
                ResponseLogic::setMsg('物品：' . $value['name'] . '库存不足。剩余库存数量：' . $realRemainNumber);
                return false;
            }

            #计算金额
            $totalPrice = $this->getTotalPrice($value['id'],$lockCount,$value['number']);
            $value['total_price'] = $totalPrice;
            $applyTotalPrice = bcadd($applyTotalPrice,$totalPrice,2);
        }

        unset($value);

        $materialNameArr = array_column($detail,'name');

        $processList = ApprovalProcessModel::getProcessList([
            'total_price' => $applyTotalPrice,
            'purpose' => $params['purpose'],
            'material_name' => $materialNameArr,
        ]);

        if(empty($processList)){
            ResponseLogic::setMsg('审批流程不存在');
            return false;
        }

        #申领主表数据
        $applyUpdateData = [
            'maap_category_id' => $params['category_id'],
            'maap_admin_id' => AuthLogic::$userId,
            'maap_status' => 1,
            'maap_total_price' => $applyTotalPrice,
            'maap_purpose' => $params['purpose'],
            'maap_remark' => $params['remark'] ?? '',
        ];

        DB::beginTransaction();

        try {
            if(MaterialApply::query()->where(['maap_id' => $params['id']])->update($applyUpdateData) === false){
                DB::rollBack();
                ResponseLogic::setMsg('更新申领表失败');
                return false;
            }

            #删除原本详情记录
            MaterialApplyDetail::query()->where(['maap_apply_id' => $params['id']])->delete();

            #申领详情表
            $applyDetailInsertData = [];
            foreach ($detail as $key => $value){
                $applyDetailInsertData[] = [
                    'maap_apply_id' => $params['id'],
                    'maap_material_id' => $value['id'],
                    'maap_number' => $value['number'],
                    'maap_total_price' => $value['total_price'],
                    'maap_status' => 1,
                ];
            }

            if(MaterialApplyDetail::query()->insert($applyDetailInsertData) === false){
                DB::rollBack();
                ResponseLogic::setMsg('插入申领详情表失败');
                return false;
            }

            #删除原本文件记录
            File::query()->where(['file_relation_id' => $params['id'],'file_type' => 'material_apply'])->delete();
            if(!empty($fileList)){
                $fileInsertData = [];
                foreach ($fileList as $key => $value){
                    $fileInsertData[] = [
                        'file_relation_id' => $params['id'],
                        'file_type' => 'material_apply',
                        'file_name' => $value['name'],
                        'file_ext' => $value['ext'],
                        'file_path' => $value['url'],
                    ];
                }

                if(File::query()->insert($fileInsertData) === false){
                    DB::rollBack();
                    ResponseLogic::setMsg('插入附件失败');
                    return false;
                }
            }


            #审批主表数据
            $approvalInsertData = [
                'appr_sn' => Approval::getSn(),
                'appr_name' => $params['name'],
                'appr_reason' => $params['reason'],
                'appr_admin_id' => AuthLogic::$userId,
                'appr_relation_id' => $params['id'],
                'appr_type' => 1,
                'appr_status' => 1,
//                'appr_relation_approval_id' => $params['relation_id'] ?? 0
            ];

            $approvalId = Approval::query()->insertGetId($approvalInsertData);

            if($approvalId === false){
                DB::rollBack();
                ResponseLogic::setMsg('插入审批表失败');
                return false;
            }

            #删除关联审批单数据
            if(ApprovalRelation::query()->where(['apre_approval_id' => $approvalId])->delete() === false){
                DB::rollBack();
                ResponseLogic::setMsg('删除关联审批信息失败');
                return false;
            }

            #关联审批单
            if(!empty($params['relation_id'])){
                $relationInsert = [];
                $relationIds = explode(',',$params['relation_id']);
                foreach ($relationIds as $key => $value){
                    $relationInsert[] = [
                        'apre_approval_id' => $approvalId,
                        'apre_relation_id' => $value
                    ];
                }

                if(ApprovalRelation::query()->insert($relationInsert) === false){
                    DB::rollBack();
                    ResponseLogic::setMsg('插入关联审批信息失败');
                    return false;
                }
            }

            $approvalProcessInsertData = [];
            #审批流程表数据
            foreach ($processList as $key => $value){
                $approvalProcessInsertData[] = [
                    'appr_approval_id' => $approvalId,
                    'appr_admin_id' => $value['admin_id'],
                    'appr_type' => $value['type'],
                    'appr_index' => $value['index'],
                    'appr_status' => 1,
                ];
            }

            if(ApprovalProcess::query()->insert($approvalProcessInsertData) === false){
                DB::rollBack();
                ResponseLogic::setMsg('插入审批流程表失败');
                return false;
            }

            #进入第下一个流程
            if(!ApprovalProcess::setNextProcess($approvalId)){
                DB::rollBack();
                return false;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ResponseLogic::setMsg($e->getMessage());
            return false;
        }

        DB::commit();
        return ['id' => $params['id']];
    }

    public function delete($params)
    {
        $data = MaterialPurchase::getDataById($params['id']);
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        if($data['mapu_status'] != 1){
            ResponseLogic::setMsg('申购记录不为待审批状态，不能删除');
            return false;
        }

        MaterialPurchaseDetail::query()->where(['mapu_pid' => $params['id']])->delete();
        MaterialPurchase::query()->where(['mapu_id' => $params['id']])->delete();
        MaterialPurchase::delCacheById($params['id']);
        return [];
    }

    public function approval($id,$status = 2)
    {
        $data = MaterialApply::query()->where(['maap_id' => $id])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        if($data['maap_status'] != 1){
            ResponseLogic::setMsg('申领单不为审批中！');
            return false;
        }

        if(MaterialApply::query()->where(['maap_id' => $id])->update(['maap_status' => $status]) === false){
            ResponseLogic::setMsg('更新申领单状态失败！');
            return false;
        }
        return true;
    }

    public function cancel($params)
    {
        $data = MaterialApply::query()->where(['maap_id' => $params['id']])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        if($data['maap_status'] != 1){
            ResponseLogic::setMsg('申领单不为审批中！');
            return false;
        }

        if(MaterialApply::query()->where(['maap_id' => $params['id']])->update(['maap_status' => 0]) === false){
            ResponseLogic::setMsg('更新申领单状态失败！');
            return false;
        }
        return [];
    }

    public function handle($params)
    {
        $detail = ToolsLogic::jsonDecode($params['detail']);

        if(empty($detail)){
            ResponseLogic::setMsg('详情数据有误！');
            return false;
        }

        $applyData = MaterialApply::query()->where(['maap_id' => $params['id']])->first();
        if(!$applyData){
            ResponseLogic::setMsg('审批单不存在！');
            return false;
        }

        $applyData = $applyData->toArray();

        if(!in_array($applyData['maap_status'],[2,3])){
            ResponseLogic::setMsg('审批单不为待出库！');
            return false;
        }

        $applyDetailArr = MaterialApplyDetail::query()
            ->whereIn('maap_id',array_column($detail,'id'))
            ->where('maap_apply_id','=',$params['id'])
            ->where('maap_status','=',1)
            ->select(['maap_id'])->pluck('maap_id')->toArray();

        $flowDataArr = MaterialFlow::query()
            ->whereIn('mafl_id',array_column($detail,'flow_id'))
            ->select(['mafl_id'])->pluck('mafl_id')->toArray();


        $existFlowArr = MaterialApplyDetail::query()
            ->whereIn('maap_flow_id',array_column($detail,'flow_id'))
            ->select(['maap_flow_id'])->pluck('maap_flow_id')->toArray();

        $updateData = [];

        foreach ($detail as $key => $value){
            if(!in_array($value['id'],$applyDetailArr)){
                ResponseLogic::setMsg('申领明细不存在！');
                return false;
            }

            if(empty($value['flow_id'])){
                continue;
            }

            if(!in_array($value['flow_id'],$flowDataArr)){
                ResponseLogic::setMsg('出库数据不存在！');
                return false;
            }

            if(in_array($value['flow_id'],$existFlowArr)){
                ResponseLogic::setMsg('存在已办理出库！');
                return false;
            }

            $updateData[] = $value;
        }

        DB::beginTransaction();

        foreach ($updateData as $key => $value){
            if(MaterialApplyDetail::query()->where(['maap_id' => $value['id']])->update(['maap_flow_id' => $value['flow_id'],'maap_status' => 2]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('更新申购单详情表失败');
                return false;
            }
        }

        #判断是否已完成申购单  如果完成把主申购单变成完成  如果未完成则变成出库中
        if(!MaterialApplyDetail::query()->where(['maap_apply_id' => $params['id'],'maap_status' => 1])->exists()){
            if(MaterialApply::query()->where(['maap_id' => $params['id']])->update(['maap_status' => 4]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('更新申购单状态失败');
                return false;
            }
        }else{
            if(MaterialApply::query()->where(['maap_id' => $params['id']])->update(['maap_status' => 3]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('更新申购单状态失败');
                return false;
            }
        }

        DB::commit();

        return [];
    }
}
