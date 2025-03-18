<?php

namespace App\Models;

use App\Http\Logic\AuthLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;

class ApprovalProcessModel extends BaseModel
{
    protected $table   = 'approval_process_model';
    public $timestamps = null;

    public static function getProcessList($conditionParams,$type = 1)
    {
        $processList = self::query()->where(['appr_approval_type' => $type])->orderBy('appr_index','asc')->get()->toArray();
        $list = [];
        $approvalAdminId = AuthLogic::$userId;
        $departmentId = Admin::query()->where(['admin_id' => AuthLogic::$userId])->value('admin_department_id') ?: 0;
        $index = 1;
        foreach ($processList as $key => $value){
            if($value['appr_mode'] == 1){
                #审批人审批
                $conditionList = ToolsLogic::jsonDecode($value['appr_condition_json']);
                if(!empty($conditionList) && !self::checkCondition($conditionList,$conditionParams)){
                    continue;
                }

                #如果重复审批  则跳过
                if($value['appr_type'] == 1 && $approvalAdminId == $value['appr_admin_id']){
                    continue;
                }

                $list[] = [
                    'admin_id' => $value['appr_admin_id'],
                    'type' => $value['appr_type'],
                    'index' => $index,
                ];

                $index++;

                if($value['appr_type'] == 1){
                    $approvalAdminId = $value['appr_admin_id'];
                }
            }else{
                #直属领导审批 获取直属领导
                $leaderArr = Department::getDepartmentLeaderArr($departmentId);
                foreach ($leaderArr as $leaderId){
                    #如果重复审批  则跳过
                    if($value['appr_type'] == 1 && $approvalAdminId == $leaderId){
                        continue;
                    }

                    $list[] = [
                        'admin_id' => $leaderId,
                        'type' => $value['appr_type'],
                        'index' => $index,
                    ];
                    $index++;
                    if($value['appr_type'] == 1){
                        $approvalAdminId = $leaderId;
                    }
                }
//                print_r($leaderArr);die;
            }

        }
//        print_r($list);die;
        return $list;
    }

    /**检查条件
     * @param $conditionList
     * @param $conditionParams
     * @return bool
     */
    public static function checkCondition($conditionList, $conditionParams)
    {
        foreach ($conditionList as $key => $value){
            switch ($value['type']){
                case "operation":
                    if(!isset($conditionParams[$value['key']])){
                        return false;
                    }

                    if($value['symbol'] == '>=' && $conditionParams[$value['key']] >= $value['value']){
                        break;
                    }

                    if($value['symbol'] == '=' && $conditionParams[$value['key']] == $value['value']){
                        break;
                    }
                    return false;
                default:
                    return false;
            }
        }

        return true;
    }
}
