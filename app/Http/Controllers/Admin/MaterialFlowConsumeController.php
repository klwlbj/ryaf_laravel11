<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\MaterialFlowConsumeLogic;
use App\Http\Logic\MaterialFlowLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialFlowConsumeController
{

    public function getList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowConsumeLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function addConsumeFlow(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'flow_id' => 'required',
            'number' => 'required',
            'date' => 'required',
            'admin_id' => 'required',
        ],[
            'flow_id.required' => '流水id不得为空',
            'number.required' => '消耗数量不得为空',
            'date.required' => '消耗日期不得为空',
            'admin_id.required' => '使用人不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowConsumeLogic::getInstance()->addConsumeFlow($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getConsumeList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'flow_id' => 'required',
        ],[
            'flow_id.required' => '流水id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowConsumeLogic::getInstance()->getConsumeList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }

        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
