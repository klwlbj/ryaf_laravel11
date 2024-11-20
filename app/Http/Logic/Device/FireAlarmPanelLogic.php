<?php

namespace App\Http\Logic\Device;

use App\Http\Library\FireAlarmPanel\HuiXiao;
use App\Http\Logic\BaseLogic;
use App\Http\Logic\ResponseLogic;
use App\Models\UitdCommand;

class FireAlarmPanelLogic extends BaseLogic
{
    public function muffling($params)
    {
        $fireAlarmPanel = new HuiXiao();

        $commandData = UitdCommand::query()->where(['uico_master_id' => $params['id'],'uico_type' => 1])->first();

        if(!$commandData){
            ResponseLogic::setMsg('指令不存在');
            return false;
        }
        $commandData = $commandData->toArray();
        $commandStr = str_replace(" ", "", $commandData['uico_open_command']);
        $res = $fireAlarmPanel->sendCommand($params['id'], $commandStr);
        if(!$res){
            return false;
        }

        return ['result' => $res];
    }

    public function setTime($params)
    {
        $fireAlarmPanel = new HuiXiao();

        $res = $fireAlarmPanel->setTime($params['id']);

        if(!$res){
            return false;
        }

        return ['result' => $res];
    }

    public function setIpPort($params)
    {
        $fireAlarmPanel = new HuiXiao();

        $res = $fireAlarmPanel->setIpPort($params['id']);

        if(!$res){
            return false;
        }

        return ['result' => $res];
    }

    public function reset($params)
    {
        $fireAlarmPanel = new HuiXiao();

        $commandData = UitdCommand::query()->where(['uico_master_id' => $params['id'],'uico_type' => 2])->first();

        if(!$commandData){
            ResponseLogic::setMsg('指令不存在');
            return false;
        }
        $commandData = $commandData->toArray();
        $commandStr = str_replace(" ", "", $commandData['uico_open_command']);
        $res = $fireAlarmPanel->sendCommand($params['id'], $commandStr);

        if(!$res){
            return false;
        }

        return ['result' => $res];
    }

    public function setMode($params)
    {
        $fireAlarmPanel = new HuiXiao();
        if($params['mode'] == 1){
            $commandData = UitdCommand::query()->where(['uico_master_id' => $params['id'],'uico_type' => 3])->first();
        }else{
            $commandData = UitdCommand::query()->where(['uico_master_id' => $params['id'],'uico_type' => 4])->first();
        }

        if(!$commandData){
            ResponseLogic::setMsg('指令不存在');
            return false;
        }

        $commandData = $commandData->toArray();
        $commandStr = str_replace(" ", "", $commandData['uico_open_command']);
        $res = $fireAlarmPanel->sendCommand($params['id'], $commandStr);

        if(!$res){
            return false;
        }

        return ['result' => $res];
    }

    public function operateDetector($params)
    {
        $fireAlarmPanel = new HuiXiao();

        $commandData = UitdCommand::query()
            ->where(['uico_master_id' => $params['id'],'uico_detector_code' => $params['detector_code']])
            ->whereIn('uico_type',[5,6])
            ->first();

        if(!$commandData){
            ResponseLogic::setMsg('指令不存在');
            return false;
        }

        $commandData = $commandData->toArray();
        if($params['status'] == 1){
            $commandStr = str_replace(" ", "", $commandData['uico_open_command']);
            $res = $fireAlarmPanel->sendCommand($params['id'], $commandStr);
        }else{
            $commandStr = str_replace(" ", "", $commandData['uico_close_command']);
            $res = $fireAlarmPanel->sendCommand($params['id'], $commandStr);
        }

        if(!$res){
            return false;
        }

        return ['result' => $res];
    }
}
