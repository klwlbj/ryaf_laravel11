<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\MaterialFlowLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialFlowController
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

        $res = MaterialFlowLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getSnList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowLogic::getInstance()->getSnList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function inComing(Request $request)
    {
        $params = $request->all();

//        print_r($params);die;

        $validate = Validator::make($params, [
            'material_id' => 'required',
            'warehouse_id' => 'required',
            'number' => 'required',
            'datetime' => 'required',
            'production_date' => 'required',
            'expire_date' => 'required',
            'verify_user_id' => 'required',
        ],[
            'material_id.required' => '物品不得为空',
            'warehouse_id.required' => '仓库不得为空',
            'number.required' => '数量不得为空',
            'datetime.required' => '入库日期不得为空',
            'production_date.required' => '生产日期不得为空',
            'expire_date.required' => '质保期不得为空',
            'verify_user_id.required' => '最终确认人不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowLogic::getInstance()->inComing($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function outComing(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'material_id' => 'required',
            'warehouse_id' => 'required',
            'number' => 'required',
            'datetime' => 'required',
            'purpose' => 'required',
            'apply_user_id' => 'required',
            'receive_user_id' => 'required',
            'verify_user_id' => 'required',
        ],[
            'material_id.required' => '物品不得为空',
            'warehouse_id.required' => '仓库不得为空',
            'number.required' => '数量不得为空',
            'datetime.required' => '出库日期不得为空',
            'purpose.required' => '用途不得为空',
            'apply_user_id.required' => '申请人不得为空',
            'receive_user_id.required' => '领用人不得为空',
            'verify_user_id.required' => '最终确认人不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowLogic::getInstance()->outComing($params);
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
            'id.required' => '流水id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowLogic::getInstance()->getInfo($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function inComingUpdate(Request $request)
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

        $res = MaterialFlowLogic::getInstance()->inComingUpdate($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function verify(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => '物品不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowLogic::getInstance()->verify($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function setPrice(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
            'price_tax' => 'required',
            'tax' => 'required',
        ],[
            'id.required' => '物品不得为空',
            'price_tax.required' => '单价(含税)不得为空',
            'tax.required' => '税率不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowLogic::getInstance()->setPrice($params);
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
            'id.required' => '流水不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowLogic::getInstance()->cancel($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
