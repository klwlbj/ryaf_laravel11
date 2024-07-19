<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\MaterialLogic;
use App\Http\Logic\OrderLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController
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

        $res = OrderLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function addAccountFlow(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'order_id' => 'required',
            'datetime' => 'required',
            'pay_way' => 'required',
            'funds_received' => 'required',
        ],[
            'order_id.required' => '订单id不得为空',
            'datetime.required' => '回款日期不得为空',
            'pay_way.required' => '支付方式不得为空',
            'funds_received.required' => '回款金额不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = OrderLogic::getInstance()->addAccountFlow($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getAccountFlow(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'order_id' => 'required',
        ],[
            'order_id.required' => '订单id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = OrderLogic::getInstance()->getAccountFlow($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function approveAccountFlow(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => '流水id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = OrderLogic::getInstance()->approveAccountFlow($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
