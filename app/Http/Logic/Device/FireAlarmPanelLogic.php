<?php

namespace App\Http\Logic\Device;

use App\Http\Library\FireAlarmPanel\HuiXiao;
use App\Http\Logic\BaseLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\IotNotification;
use App\Models\IotNotificationAlert;
use App\Models\IotNotificationSelfCheck;
use App\Models\Mainframe;
use App\Models\SmokeDetector;
use App\Models\UitdCommand;
use Illuminate\Support\Facades\DB;

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

    public function operate($params)
    {
        $fireAlarmPanel = new HuiXiao();

        $commandData = UitdCommand::query()
            ->where(['uico_master_id' => $params['id'],'uico_device_code' => $params['device_code']])
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
            $update = [
                'uico_status' => 1
            ];
        }else{
            $commandStr = str_replace(" ", "", $commandData['uico_close_command']);
            $res = $fireAlarmPanel->sendCommand($params['id'], $commandStr);
            $update = [
                'uico_status' => 0
            ];
        }

        if(!$res){
            return false;
        }

        UitdCommand::query()->where(['uico_id' => $commandData['uico_id']])->update($update);

        return ['result' => $res];
    }

    public function getDeviceList($params)
    {
        $list = UitdCommand::query()
            ->leftJoin('smoke_detector','smoke_detector.smde_imei','=','uitd_command.uico_device_code')
            ->where(['uico_master_id' => $params['id']])
            ->whereIn('uico_type',[5,6])
            ->select([
                'uico_master_id as master_id',
                'uico_device_code as device_code',
                'uico_name as name',
                'smde_building_no as position',
                'uico_status as status'
            ])
            ->get()->toArray();

        foreach ($list as $key => &$value){
            $value['name'] = $value['name'] . '_' . $value['device_code'];
        }

        unset($value);

        return ['list' => $list];
    }

    public function pushData($params)
    {

        $data = ToolsLogic::jsonDecode($params['data']);

        $mainId = Mainframe::query()->where(['mafr_no' => $params['id']])->value('mafr_id') ?: 0;
        if(empty($mainId)){
            ResponseLogic::setMsg('主机设备不存在');
            return false;
        }

        #如果是其他类型  则插入警报表
        if(!in_array($params['type'],['heartbeat','fire_alert','malfunction'])){
            $insertData = [
                'iono_body' => $params['data'],
                'iono_platform' => 'HUIXIAO',
                'iono_msg_at' => strtotime($data['datetime']['value']),
//                'iono_msg_imei' => $params['id'],
                'iono_type' => -1,
//                'iono_imei' => $params['id'],
                'iono_mafr_id' => $mainId
            ];


            IotNotification::query()->insert($insertData);
            return [];
        }

        try {
            if($params['type'] == 'heartbeat'){

                $insertData = [
                    'iono_body' => $params['data'],
                    'iono_platform' => 'HUIXIAO',
                    'iono_msg_at' => strtotime($data['datetime']['value']),
//                    'iono_msg_imei' => $params['id'],
                    'iono_type' => 21,
//                    'iono_imei' => $params['id'],
                    'iono_mafr_id' => $mainId
                ];


                $ionoId = IotNotification::query()->insertGetId($insertData);

                $insertData['iono_id'] = $ionoId;

                IotNotificationSelfCheck::query()->insert($insertData);

                #更新消控主机主表心跳包时间
                Mainframe::query()->where(['mafr_id' => $mainId])->update(['mafr_last_heart_beat' => $data['datetime']['value']]);

            }elseif(in_array($params['type'],['fire_alert','malfunction'])){
                if($params['type'] == 'fire_alert'){
                    if($data['status']['value'] == '点型感烟'){
                        $alertType = 1;
                    }elseif ($data['status']['value'] == '点型感温'){
                        $alertType = 3;
                    }else{
                        $alertType = 1;
                    }

                    $imei = $data['probe_code']['value'] ?? '';
                    if(empty($imei)){
                        ResponseLogic::setMsg('imei不存在');
                        return false;
                    }

                    $smdeId = SmokeDetector::query()
                        ->where(['smde_mafr_id' => $mainId,'smde_imei' => $imei])->value('smde_id') ?: 0;

                    if(empty($smdeId)){
                        ResponseLogic::setMsg('设备不存在');
                        return false;
                    }
                }

                if($params['type'] == 'malfunction'){
                    $imei = $data['probe_code']['value'] ?? '';
                    if(!empty($imei)){
                        $smdeId = SmokeDetector::query()
                            ->where(['smde_mafr_id' => $mainId,'smde_imei' => $imei])->value('smde_id') ?: null;
                    }else{
                        $smdeId = null;
                    }

                    $alertType = 22;
                }


                $insertData = [
                    'iono_body' => $params['data'],
                    'iono_platform' => 'HUIXIAO',
                    'iono_msg_at' => strtotime($data['datetime']['value']),
                    'iono_msg_imei' => $imei ?? '',
                    'iono_type' => $alertType ?? -1,
                    'iono_imei' => $imei ?? '',
                    'iono_smde_id' => $smdeId ?? null,
                    'iono_mafr_id' => $mainId
                ];

                $ionoId = IotNotification::query()->insertGetId($insertData);

                $insertData['iono_id'] = $ionoId;
                $insertData['iono_status'] = '待处理';

                IotNotificationAlert::query()->insert($insertData);
            }
        } catch (\Exception $e) {
            ResponseLogic::setMsg($e->getMessage());
            return false;
        }



        return [];
    }
}
