<?php

namespace App\Models;

use App\Http\Logic\ResponseLogic;

class ApprovalProcess extends BaseModel
{
    protected $table   = 'approval_process';
    public $timestamps = null;

    public static function getCurrentData($approvalId)
    {
        $data = self::query()->where(['appr_approval_id' => $approvalId,'appr_status' => 1])->first();
        if(!$data){
            ResponseLogic::setMsg('当前审批中流程不存在');
            return false;
        }

        return $data->toArray();
    }

    public static function getNextData($currentData = [])
    {
        if(empty($currentData)){
            $currentIndex = 0;
        }else{
            $currentIndex = $currentData['appr_index'];
        }

        $nextData = self::query()
            ->where('appr_index','>',$currentIndex)
            ->where(['appr_status' => 0])
            ->orderBy('appr_index','asc')->first();

        if(!$nextData){
            return [];
        }

        return $nextData->toArray();
    }
}
