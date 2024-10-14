<?php

namespace App\Http\Logic;

use App\Models\Approval;
use App\Models\ApprovalProcess;

class ApprovalProcessLogic extends BaseLogic
{
    public function agree($id)
    {
        $approvalData = Approval::query()->where(['appr_id' => $id])->first();
        if(!$approvalData){
            ResponseLogic::setMsg('审批数据不存在');
            return false;
        }

        $approvalData = $approvalData->toArray();

        if($approvalData['appr_status'] !== 0){
            ResponseLogic::setMsg('审批不为待审批状态，不能操作');
            return false;
        }

        #获取当前审批流程
        $currentProcess = ApprovalProcess::getCurrentData($id);
        if(!$currentProcess){
            return false;
        }

        #获取下一个审批人
        $nextProcess = ApprovalProcess::getNextData($currentProcess);

        $pass = false;
        if(empty($nextProcess)){
            $pass = true;
        }


    }
}
