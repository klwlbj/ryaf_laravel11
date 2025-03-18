<?php

namespace App\Models;

use App\Http\Logic\ResponseLogic;

class ApprovalProcess extends BaseModel
{
    protected $table   = 'approval_process';
    public $timestamps = null;

    public static function getCurrentData($approvalId)
    {
        $data = self::query()->where(['appr_approval_id' => $approvalId,'appr_status' => 2])->first();
        if(!$data){
            ResponseLogic::setMsg('当前审批中流程不存在');
            return false;
        }

        return $data->toArray();
    }

    public static function setNextProcess($id,$currentData = [])
    {
        #获取下个审批数据
        $processData = self::getNextApprovalData($id,$currentData);

        #获取下个抄送数据
        $carbonCopyIds = self::getNextCarbonCopyIds($id,$currentData,$processData);

        #处理抄送
        if(!empty($carbonCopyIds)){
            if(self::query()->whereIn('appr_id',$carbonCopyIds)->update(['appr_status' => 3,'appr_complete_date' => date('Y-m-d H:i:s')]) === false){
                ResponseLogic::setMsg('抄送失败');
                return false;
            }
        }
        if(empty($processData)){
            #该审批单已完成
            return [
                'pass' => true,
                'process' => [],
            ];
        }

        #更新下个流程
        if(self::query()->where(['appr_id' => $processData['appr_id']])->update(['appr_status' => 2]) === false){
            ResponseLogic::setMsg('更新下一步流程失败');
            return false;
        }

        return [
            'pass' => false,
            'process' => $processData,
        ];
    }

    public static function getNextCarbonCopyIds($id,$currentData = [],$nextData = [])
    {
        if(empty($currentData)){
            $currentIndex = 0;
        }else{
            $currentIndex = $currentData['appr_index'];
        }

        $query = self::query()
            ->where('appr_index','>',$currentIndex)
            ->where(['appr_approval_id' => $id,'appr_status' => 1,'appr_type' => 2]);

        if(!empty($nextData)){
            $query->where('appr_index','<',$nextData['appr_index']);
        }

        return $query->pluck('appr_id')->toArray();
    }

    public static function getNextApprovalData($id,$currentData = [])
    {
        if(empty($currentData)){
            $currentIndex = 0;
        }else{
            $currentIndex = $currentData['appr_index'];
        }

        $nextData = self::query()
            ->where('appr_index','>',$currentIndex)
            ->where(['appr_approval_id' => $id,'appr_status' => 1,'appr_type' => 1])
            ->orderBy('appr_index','asc')->first();

        if(!$nextData){
            return [];
        }

        return $nextData->toArray();
    }
}
