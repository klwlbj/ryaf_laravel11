<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\MaterialLogic;
use App\Http\Logic\OrderLogic;
use App\Http\Logic\ReceivableAccountLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReceivableAccountController
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

        $res = ReceivableAccountLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'receivable_id' => 'required',
        ],[
            'receivable_id.required' => '记录id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ReceivableAccountLogic::getInstance()->getInfo($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function update(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'receivable_id' => 'required',
        ],[
            'receivable_id.required' => '订单id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ReceivableAccountLogic::getInstance()->update($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function delete(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'receivable_id' => 'required',
        ],[
            'receivable_id.required' => '订单id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ReceivableAccountLogic::getInstance()->delete($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function addFlow(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'receivable_id' => 'required',
            'datetime' => 'required',
            'pay_way' => 'required',
            'funds_received' => 'required',
        ],[
            'receivable_id.required' => '订单id不得为空',
            'datetime.required' => '回款日期不得为空',
            'pay_way.required' => '支付方式不得为空',
            'funds_received.required' => '回款金额不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ReceivableAccountLogic::getInstance()->addFlow($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getFlow(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'receivable_id' => 'required',
        ],[
            'receivable_id.required' => '订单id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ReceivableAccountLogic::getInstance()->getFlow($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function import(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'data' => 'required',
        ],[
            'data.required' => '数据不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ReceivableAccountLogic::getInstance()->import($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function syncOrder(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'start_date' => 'required',
            'end_date' => 'required',
        ],[
            'start_date.required' => '开始时间不得为空',
            'end_date.required' => '结束时间不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = ReceivableAccountLogic::getInstance()->syncOrder($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
