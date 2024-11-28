<?php

namespace App\Http\Controllers\Device;

use App\Http\Logic\Device\FireAlarmPanelLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FireAlarmPanelController
{
    public function muffling(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => '设备ID 不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FireAlarmPanelLogic::getInstance()->muffling($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function setTime(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => '设备ID 不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FireAlarmPanelLogic::getInstance()->setTime($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function setIpPort(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
            'ip' => 'required',
            'port' => 'required',
        ],[
            'id.required' => '设备ID 不得为空',
            'ip.required' => 'ip 不得为空',
            'port.required' => 'port 不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FireAlarmPanelLogic::getInstance()->setIpPort($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function reset(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => '设备ID 不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FireAlarmPanelLogic::getInstance()->reset($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function setMode(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
            'mode' => 'required'
        ],[
            'id.required' => '设备ID 不得为空',
            'mode.required' => '模式 不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FireAlarmPanelLogic::getInstance()->setMode($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function operateDetector(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
            'detector_code' => 'required',
            'status' => 'required',
        ],[
            'id.required' => '设备ID 不得为空',
            'detector_code.required' => '传感器编码 不得为空',
            'status.required' => '开关状态 不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FireAlarmPanelLogic::getInstance()->operateDetector($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function pushData(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
            'type' => 'required',
            'data' => 'required',
        ],[
            'id.required' => '设备ID 不得为空',
            'type.required' => '数据类型 不得为空',
            'data.required' => '数据 不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FireAlarmPanelLogic::getInstance()->pushData($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
