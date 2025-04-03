<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\ApprovalLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApprovalController
{
    public function getList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'type' => 'required',
        ],[
            'type.required' => 'id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ApprovalLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => 'id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ApprovalLogic::getInstance()->getInfo($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function updateInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => 'id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ApprovalLogic::getInstance()->updateInfo($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function agree(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => 'id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ApprovalLogic::getInstance()->agree($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function reject(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => 'id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ApprovalLogic::getInstance()->reject($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function cancel(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => 'id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ApprovalLogic::getInstance()->cancel($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
