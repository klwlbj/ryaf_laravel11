<?php

namespace App\Http\Logic;

use App\Models\Approval;
use App\Models\ApprovalProcess;
use App\Models\ApprovalRelation;
use Illuminate\Support\Facades\DB;


class ApprovalLogic extends BaseLogic
{
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        if($params['type'] == 'submit'){
            $query = Approval::query()
                ->leftJoin('admin','admin_id','=','appr_admin_id')
                ->where(['appr_admin_id' => AuthLogic::$userId]);
        }elseif ($params['type'] == 'wait_approval'){
            $query = ApprovalProcess::query()
                ->leftJoin('approval','approval.appr_id','=','approval_process.appr_approval_id')
                ->leftJoin('admin','admin_id','=','approval.appr_admin_id')
                ->where([
                    'approval_process.appr_admin_id' => AuthLogic::$userId,
                    'approval_process.appr_status' => 2,
                    'approval.appr_status' => 1,
                    'approval_process.appr_type' => 1,
                ]);
        }elseif ($params['type'] == 'has_approval'){
            $query = ApprovalProcess::query()
                ->leftJoin('approval','approval.appr_id','=','approval_process.appr_approval_id')
                ->leftJoin('admin','admin_id','=','approval.appr_admin_id')
                ->where([
                    'approval_process.appr_admin_id' => AuthLogic::$userId,
                    'approval_process.appr_type' => 1,
                ])
                ->whereIn('approval_process.appr_status',[3,4])
                ->whereIn('approval.appr_status',[1,2,3]);
        }elseif ($params['type'] == 'carbon_copy'){
            $query = ApprovalProcess::query()
                ->leftJoin('approval','approval.appr_id','=','approval_process.appr_approval_id')
                ->leftJoin('admin','admin_id','=','approval.appr_admin_id')
                ->where([
                    'approval_process.appr_admin_id' => AuthLogic::$userId,
                    'approval_process.appr_type' => 2,
                ])
                ->whereIn('approval_process.appr_status',[3])
                ->whereIn('approval.appr_status',[1,2,3]);
        }else{
            return ['list' => [],'total' => 0];
        }


        if(isset($params['status']) && $params['status'] !== ''){
            $query->where(['approval.appr_status' => $params['status']]);
        }

        $query->groupBy(['approval.appr_id']);

        $total = $query->count();

        $list = $query
            ->select([
                'approval.*',
                'admin_name'
            ])
            ->orderBy('appr_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        $approvalIds = array_column($list,'appr_id');

        #获取当前审批人
        $processArr = ApprovalProcess::query()
            ->leftJoin('admin','admin_id','=','appr_admin_id')
            ->whereIn('appr_approval_id',$approvalIds)
            ->where(['appr_status' => 2])
            ->select([
                'appr_approval_id',
                'admin_name'
            ])->pluck('admin_name','appr_approval_id')->toArray();

        foreach ($list as $key => &$value){
            $value['process_admin_name'] = $processArr[$value['appr_id']] ?? '';
            $value['is_cancel'] = ($value['appr_status'] == 1 && $value['appr_admin_id'] == AuthLogic::$userId);
        }

        unset($value);

        return ['list' => $list,'total' => $total];
    }

    public function getInfo($params)
    {
        $approvalData = Approval::query()
            ->leftJoin('admin','admin_id','=','appr_admin_id')
            ->leftJoin('department','depa_id','=','admin_department_id')
            ->where(['appr_id' => $params['id']])
            ->select([
                'approval.*',
                'admin_name',
                'depa_name'
            ])->first();
        if(!$approvalData){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $approvalData['process'] = ApprovalProcess::query()
            ->leftJoin('admin','admin_id','=','appr_admin_id')
            ->where(['appr_approval_id' => $approvalData['appr_id']])
            ->select(['approval_process.*','admin_name'])
            ->orderBy('appr_index','asc')->get()->toArray();

        #关联审批单
        $relationIds = ApprovalRelation::query()->where(['apre_approval_id' => $approvalData['appr_id']])->select(['apre_relation_id'])->pluck('apre_relation_id')->toArray();

        if(!empty($relationIds)){
            $approvalData['relation_approval'] = Approval::query()
                ->whereIn('appr_id',$relationIds)
                ->get()->toArray();
        }else{
            $approvalData['relation_approval'] = [];
        }

        #判断是否可审批
        $approvalAdminId = ApprovalProcess::query()
            ->where(['appr_approval_id' => $approvalData['appr_id'],'appr_status' => 2])
            ->value('appr_admin_id') ?: null;

        $approvalData['is_approval'] = $approvalAdminId == AuthLogic::$userId;

        switch ($approvalData['appr_type']){
            case 1:
                if(!empty($approvalData['appr_detail_data'])){
                    $approvalData['relation_data'] = ToolsLogic::jsonDecode($approvalData['appr_detail_data']);
                }else{
                    $approvalData['relation_data'] = MaterialApplyLogic::getInstance()->getApprovalInfo($approvalData['appr_relation_id']);
                }

                break;
            default:
                $approvalData['relation_data'] = [];
                break;
        }

        return $approvalData;

    }

    public function updateInfo($params)
    {
        $approvalData = Approval::query()->where(['appr_id' => $params['id']])->first();
        if(!$approvalData){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $update = [];

        if(!empty($params['remark'])){
            $update['appr_remark'] = $params['remark'];
        }

        if(!empty($update)){
            Approval::query()->where(['appr_id' => $params['id']])->update($update);
        }

        return [];
    }

    public function agree($params)
    {
        $approvalData = Approval::query()->where(['appr_id' => $params['id']])->first();
        if(!$approvalData){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $approvalData = $approvalData->toArray();

        if($approvalData['appr_status'] != 1){
            ResponseLogic::setMsg('该审批单不为待审批状态！');
            return false;
        }

        #获取当前流程
        $currentProcessData = ApprovalProcess::getCurrentData($params['id']);
        if(!$currentProcessData){
            return false;
        }
//        print_r($currentProcessData);die;
        if($currentProcessData['appr_admin_id'] != AuthLogic::$userId){
            ResponseLogic::setMsg('审批人不为当前账号！');
            return false;
        }

        DB::beginTransaction();
        #更新当前流程
        if(ApprovalProcess::query()->where(['appr_id' => $currentProcessData['appr_id']])->update(['appr_status' => 3,'appr_remark' => $params['remark'] ?? '','appr_complete_date' => date('Y-m-d H:i:s')]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新当前流程失败！');
            return false;
        }

        #进入下一个流程
        $processRes = ApprovalProcess::setNextProcess($params['id'],$currentProcessData);
        if(!$processRes){
            DB::rollBack();
            return false;
        }

        #如果已通过
        if($processRes['pass']){
            #更新审批主表状态
            if(Approval::query()->where(['appr_id' => $params['id']])->update(['appr_status' => 2]) === false){
                DB::rollBack();
                ResponseLogic::setMsg('更新审批表状态失败！');
                return false;
            }

            #更新关联业务数据
            switch ($approvalData['appr_type']){
                case 1:
                    if(!MaterialApplyLogic::getInstance()->approval($approvalData['appr_relation_id'],2)){
                        DB::rollBack();
                        return false;
                    }
                    break;
            }


        }

        DB::commit();

        return [];
    }

    public function reject($params)
    {
        $approvalData = Approval::query()->where(['appr_id' => $params['id']])->first();
        if(!$approvalData){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $approvalData = $approvalData->toArray();

        if($approvalData['appr_status'] != 1){
            ResponseLogic::setMsg('该审批单不为待审批状态！');
            return false;
        }

        #获取当前流程
        $currentProcessData = ApprovalProcess::getCurrentData($params['id']);
        if(!$currentProcessData){
            return false;
        }

        if($currentProcessData['appr_admin_id'] != AuthLogic::$userId){
            ResponseLogic::setMsg('审批人不为当前账号！');
            return false;
        }



        DB::beginTransaction();
        #更新当前流程
        if(ApprovalProcess::query()->where(['appr_id' => $currentProcessData['appr_id']])->update(['appr_status' => 4,'appr_remark' => $params['remark'] ?? '','appr_complete_date' => date('Y-m-d H:i:s')]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新当前流程失败！');
            return false;
        };



        #处理相应业务逻辑
        switch ($approvalData['appr_type']){
            case 1:
                if(!MaterialApplyLogic::getInstance()->approval($approvalData['appr_relation_id'],5)){
                    DB::rollBack();
                    return false;
                }
                #获取detail
                $detail = MaterialApplyLogic::getInstance()->getApprovalInfo($approvalData['appr_relation_id']);
                break;
            default:
                $detail = null;
                break;
        }

        #更新审批主表状态
        if(Approval::query()->where(['appr_id' => $params['id']])->update(['appr_status' => 3,'appr_complete_date' =>date('Y-m-d H:i:s'),'appr_detail_data' => ToolsLogic::jsonDecode($detail)]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新审批表状态失败！');
            return false;
        }

        DB::commit();

        return [];
    }

    public function cancel($params)
    {
        $approvalData = Approval::query()->where(['appr_id' => $params['id']])->first();
        if(!$approvalData){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $approvalData = $approvalData->toArray();

        if($approvalData['appr_status'] != 1){
            ResponseLogic::setMsg('该审批单不为待审批状态！');
            return false;
        }

        if($approvalData['appr_admin_id'] != AuthLogic::$userId){
            ResponseLogic::setMsg('该账号不为提审人 不能撤回！');
            return false;
        }

        DB::beginTransaction();

        #处理相应业务逻辑
        switch ($approvalData['appr_type']){
            case 1:
                if(MaterialApplyLogic::getInstance()->cancel(['id' => $approvalData['appr_relation_id']]) === false){
                    DB::rollBack();
                    return false;
                }
                #获取detail
                $detail = MaterialApplyLogic::getInstance()->getApprovalInfo($approvalData['appr_relation_id']);
                break;
            default:
                $detail = null;
                break;
        }

        #修改审批状态
        if(Approval::query()->where(['appr_id' => $params['id']])->update(['appr_status' => 0,'appr_complete_date' =>date('Y-m-d H:i:s'),'appr_detail_data' => ToolsLogic::jsonDecode($detail)]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新审批表状态失败！');
            return false;
        }

        DB::commit();

        return [];
    }


}
